<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Throwable;

final class OfferDesignService
{
    private const FORMATS = ['A4', 'A3'];
    private const ORIENTATIONS = ['portrait', 'landscape'];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT public_id, name, description, format, orientation, theme, updated_at, last_used_at
             FROM offer_designs
             WHERE user_id = :user_id
             ORDER BY COALESCE(last_used_at, updated_at) DESC, updated_at DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn (array $row): array => $this->mapSummaryRow($row), $rows);
    }

    public function findByPublicId(string $publicId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT public_id, name, description, format, orientation, theme, html, css, design_json, meta_json, updated_at, last_used_at
             FROM offer_designs
             WHERE public_id = :public_id AND user_id = :user_id
             LIMIT 1'
        );
        $stmt->bindValue(':public_id', $publicId, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return $this->mapFullRow($row);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, errors?:array<int,string>, design?:array<string,mixed>}
     */
    public function save(array $input, int $userId): array
    {
        $name = isset($input['name']) ? trim((string) $input['name']) : '';
        $html = (string) ($input['html'] ?? '');
        $css = isset($input['css']) ? (string) $input['css'] : '';
        $designJson = isset($input['design_json']) ? (string) $input['design_json'] : null;
        $description = isset($input['description']) ? trim((string) $input['description']) : null;
        $meta = isset($input['meta']) && is_array($input['meta']) ? $input['meta'] : [];
        $format = isset($input['format']) ? strtoupper((string) $input['format']) : 'A4';
        $orientation = isset($input['orientation']) ? strtolower((string) $input['orientation']) : 'portrait';
        $theme = isset($input['theme']) ? trim((string) $input['theme']) : null;
        $publicId = isset($input['public_id']) ? trim((string) $input['public_id']) : '';

        $errors = [];
        if ($name === '') {
            $errors[] = 'Inserisci il nome del layout.';
        }
        if ($html === '') {
            $errors[] = 'Il contenuto del layout non può essere vuoto.';
        }
        if (!in_array($format, self::FORMATS, true)) {
            $format = 'A4';
        }
        if (!in_array($orientation, self::ORIENTATIONS, true)) {
            $orientation = 'portrait';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile salvare il layout.',
                'errors' => $errors,
            ];
        }

        $metaJson = null;
        if ($meta !== []) {
            try {
                $metaJson = json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            } catch (Throwable) {
                $metaJson = null;
            }
        }
        $designJson = $designJson !== null && $designJson !== '' ? $designJson : null;
        $description = $description !== '' ? $description : null;

        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        if ($publicId === '') {
            $publicId = $this->generateUuid();
            $stmt = $this->pdo->prepare(
                'INSERT INTO offer_designs (public_id, user_id, name, description, format, orientation, theme, html, css, design_json, meta_json, last_used_at, created_at, updated_at)
                 VALUES (:public_id, :user_id, :name, :description, :format, :orientation, :theme, :html, :css, :design_json, :meta_json, :last_used_at, :created_at, :updated_at)'
            );
            $stmt->execute([
                ':public_id' => $publicId,
                ':user_id' => $userId,
                ':name' => $name,
                ':description' => $description,
                ':format' => $format,
                ':orientation' => $orientation,
                ':theme' => $theme,
                ':html' => $html,
                ':css' => $css,
                ':design_json' => $designJson,
                ':meta_json' => $metaJson,
                ':last_used_at' => $now,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE offer_designs
                 SET name = :name,
                     description = :description,
                     format = :format,
                     orientation = :orientation,
                     theme = :theme,
                     html = :html,
                     css = :css,
                     design_json = :design_json,
                     meta_json = :meta_json,
                     last_used_at = :last_used_at,
                     updated_at = :updated_at
                 WHERE public_id = :public_id AND user_id = :user_id'
            );
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':format' => $format,
                ':orientation' => $orientation,
                ':theme' => $theme,
                ':html' => $html,
                ':css' => $css,
                ':design_json' => $designJson,
                ':meta_json' => $metaJson,
                ':last_used_at' => $now,
                ':updated_at' => $now,
                ':public_id' => $publicId,
                ':user_id' => $userId,
            ]);

            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Layout non trovato o non autorizzato.',
                    'errors' => ['Il layout selezionato non esiste più.'],
                ];
            }
        }

        $design = $this->findByPublicId($publicId, $userId);
        return [
            'success' => true,
            'message' => 'Layout salvato con successo.',
            'design' => $design ?? ['public_id' => $publicId, 'name' => $name],
        ];
    }

    public function delete(string $publicId, int $userId): array
    {
        $stmt = $this->pdo->prepare('DELETE FROM offer_designs WHERE public_id = :public_id AND user_id = :user_id');
        $stmt->bindValue(':public_id', $publicId, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Nessun layout eliminato.',
                'errors' => ['Il layout selezionato non è disponibile.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Layout eliminato.',
        ];
    }

    public function touchLastUsed(string $publicId, int $userId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE offer_designs SET last_used_at = NOW(), updated_at = NOW() WHERE public_id = :public_id AND user_id = :user_id'
            );
            $stmt->bindValue(':public_id', $publicId, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Throwable) {
            // L'aggiornamento della traccia utilizzo non è critico.
        }
    }

    private function mapSummaryRow(array $row): array
    {
        return [
            'id' => $row['public_id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'format' => $row['format'],
            'orientation' => $row['orientation'],
            'theme' => $row['theme'] ?? null,
            'updated_at' => $row['updated_at'],
            'last_used_at' => $row['last_used_at'] ?? null,
        ];
    }

    private function mapFullRow(array $row): array
    {
        return [
            'id' => $row['public_id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'format' => $row['format'],
            'orientation' => $row['orientation'],
            'theme' => $row['theme'] ?? null,
            'html' => $row['html'],
            'css' => $row['css'] ?? '',
            'design_json' => $row['design_json'] ?? null,
            'meta' => $row['meta_json'] ? json_decode($row['meta_json'], true) : null,
            'updated_at' => $row['updated_at'],
            'last_used_at' => $row['last_used_at'] ?? null,
        ];
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
