<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use PDO;

/**
 * Gestione centralizzata delle notifiche mostrate nel layout e inviate verso canali esterni.
 */
final class SystemNotificationService
{
    private PDO $pdo;
    private ?NotificationDispatcher $dispatcher;
    private ?string $logFile;

    public function __construct(PDO $pdo, ?NotificationDispatcher $dispatcher = null, ?string $logFile = null)
    {
        $this->pdo = $pdo;
        $this->dispatcher = $dispatcher;
        $this->logFile = $logFile;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function push(string $type, string $title, string $body, array $options = []): array
    {
        $normalizedType = $type !== '' ? strtolower($type) : 'system';
        $normalizedLevel = $this->normalizeLevel((string) ($options['level'] ?? 'info'));
        $channel = isset($options['channel']) && (string) $options['channel'] !== ''
            ? strtolower((string) $options['channel'])
            : $normalizedType;
        $source = isset($options['source']) && (string) $options['source'] !== ''
            ? (string) $options['source']
            : 'system';
        $link = isset($options['link']) && (string) $options['link'] !== ''
            ? trim((string) $options['link'])
            : null;
        $recipientId = isset($options['user_id']) ? (int) $options['user_id'] : null;
        $meta = $this->normalizeMeta($options['meta'] ?? []);
        $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($metaJson === false) {
            $metaJson = '{}';
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO system_notifications (
                 type, title, body, level, channel, source, link, meta_json, recipient_user_id, is_read, created_at
             ) VALUES (:type, :title, :body, :level, :channel, :source, :link, :meta, :recipient, 0, NOW())'
        );

        $stmt->execute([
            ':type' => $normalizedType,
            ':title' => trim($title),
            ':body' => trim($body),
            ':level' => $normalizedLevel,
            ':channel' => $channel,
            ':source' => $source,
            ':link' => $link,
            ':meta' => $metaJson,
            ':recipient' => $recipientId,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $createdAt = (new DateTimeImmutable('now'))->format('c');
        $record = [
            'id' => $id,
            'type' => $normalizedType,
            'title' => trim($title),
            'body' => trim($body),
            'level' => $normalizedLevel,
            'channel' => $channel,
            'source' => $source,
            'link' => $link,
            'meta' => $meta,
            'recipient_user_id' => $recipientId,
            'created_at' => $createdAt,
            'is_read' => false,
        ];

        if ($this->dispatcher !== null) {
            $this->dispatcher->dispatch($record);
        }

        $this->log(sprintf('Notifica #%d [%s] registrata', $id, $channel));

        return $record;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, unread_count:int}
     */
    public function getTopbarFeed(?int $userId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 30));
        $conditions = 'recipient_user_id IS NULL';
        $params = [];
        if ($userId !== null) {
            $conditions = '(recipient_user_id IS NULL OR recipient_user_id = :uid)';
            $params[':uid'] = $userId;
        }

        $sql = 'SELECT id, type, title, body, level, channel, source, link, meta_json, recipient_user_id, is_read, created_at
                FROM system_notifications
                WHERE ' . $conditions . '
                ORDER BY created_at DESC
                LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        if (array_key_exists(':uid', $params)) {
            $stmt->bindValue(':uid', (int) $params[':uid'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $items = [];
        foreach ($rows as $row) {
            $meta = [];
            if (!empty($row['meta_json'])) {
                $decoded = json_decode((string) $row['meta_json'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $isRead = (int) ($row['is_read'] ?? 0) === 1;
            $items[] = [
                'id' => (int) $row['id'],
                'type' => (string) ($row['type'] ?? 'system'),
                'title' => (string) ($row['title'] ?? ''),
                'body' => (string) ($row['body'] ?? ''),
                'level' => $this->normalizeLevel((string) ($row['level'] ?? 'info')),
                'channel' => (string) ($row['channel'] ?? ($row['type'] ?? 'system')),
                'source' => (string) ($row['source'] ?? 'system'),
                'link' => isset($row['link']) && $row['link'] !== '' ? (string) $row['link'] : null,
                'meta' => $meta,
                'is_read' => $isRead,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return [
            'items' => $items,
            'unread_count' => $this->countUnread($userId),
        ];
    }

    public function markAllRead(?int $userId = null): int
    {
        if ($userId === null) {
            $stmt = $this->pdo->prepare('UPDATE system_notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0');
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE system_notifications
                 SET is_read = 1, read_at = NOW()
                 WHERE is_read = 0 AND (recipient_user_id IS NULL OR recipient_user_id = :uid)'
            );
            $stmt->execute([':uid' => $userId]);
        }

        $count = (int) $stmt->rowCount();
        if ($count > 0) {
            $this->log('Notifiche segnate come lette: ' . $count);
        }

        return $count;
    }

    public function markAsRead(int $notificationId, ?int $userId = null): bool
    {
        $sql = 'UPDATE system_notifications SET is_read = 1, read_at = NOW() WHERE id = :id';
        $params = [':id' => $notificationId];
        if ($userId !== null) {
            $sql .= ' AND (recipient_user_id IS NULL OR recipient_user_id = :uid)';
            $params[':uid'] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $updated = (int) $stmt->rowCount() > 0;
        if ($updated) {
            $this->log('Notifica #' . $notificationId . ' segnata come letta');
        }

        return $updated;
    }

    private function countUnread(?int $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM system_notifications WHERE is_read = 0';
        $params = [];
        if ($userId !== null) {
            $sql .= ' AND (recipient_user_id IS NULL OR recipient_user_id = :uid)';
            $params[':uid'] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param mixed $meta
     * @return array<string, mixed>
     */
    private function normalizeMeta($meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        return ['value' => $meta];
    }

    private function normalizeLevel(string $level): string
    {
        $normalized = strtolower(trim($level));
        return match ($normalized) {
            'success', 'ok', 'positive' => 'success',
            'warning', 'warn', 'alert' => 'warning',
            'danger', 'error', 'fail' => 'danger',
            default => 'info',
        };
    }

    private function log(string $message): void
    {
        if ($this->logFile === null) {
            return;
        }

        $line = sprintf('[%s] %s%s', date('c'), $message, PHP_EOL);
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
