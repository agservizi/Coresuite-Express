<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use PDO;

final class DiscountCampaignService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        $sql = <<<SQL
SELECT dc.*, stats.usage_count, stats.total_discount, stats.last_used_at
FROM discount_campaigns dc
LEFT JOIN (
    SELECT s.discount_campaign_id AS campaign_id,
           COUNT(*) AS usage_count,
           COALESCE(SUM(s.discount), 0) AS total_discount,
           MAX(s.created_at) AS last_used_at
    FROM sales s
    WHERE s.discount_campaign_id IS NOT NULL AND s.status != 'Cancelled'
    GROUP BY s.discount_campaign_id
) stats ON stats.campaign_id = dc.id
ORDER BY dc.created_at DESC
SQL;

        $stmt = $this->pdo->query($sql);

        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActive(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM discount_campaigns
             WHERE is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY name ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM discount_campaigns WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $campaign = $stmt->fetch();

        return $campaign === false ? null : $campaign;
    }

    public function findActive(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM discount_campaigns
             WHERE id = :id
               AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())'
        );
        $stmt->execute([':id' => $id]);
        $campaign = $stmt->fetch();

        return $campaign === false ? null : $campaign;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, error?:string}
     */
    public function create(array $input): array
    {
        $normalized = $this->normalizeCampaignInput($input);
        if (!($normalized['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'Impossibile salvare la campagna.',
                'error' => $normalized['error'] ?? 'Dati non validi.',
            ];
        }

        $data = $normalized['data'];

        $stmt = $this->pdo->prepare(
            'INSERT INTO discount_campaigns (name, description, type, value, is_active, starts_at, ends_at)
             VALUES (:name, :description, :type, :value, 1, :starts_at, :ends_at)'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':type' => $data['type'],
            ':value' => $data['value'],
            ':starts_at' => $data['starts_at'],
            ':ends_at' => $data['ends_at'],
        ]);

        return [
            'success' => true,
            'message' => 'Campagna sconto creata.',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, error?:string}
     */
    public function update(int $id, array $input): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la campagna.',
                'error' => 'Identificativo campagna non valido.',
            ];
        }

        $existing = $this->find($id);
        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la campagna.',
                'error' => 'Campagna non trovata.',
            ];
        }

        $normalized = $this->normalizeCampaignInput($input);
        if (!($normalized['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la campagna.',
                'error' => $normalized['error'] ?? 'Dati non validi.',
            ];
        }

        $data = $normalized['data'];

        $stmt = $this->pdo->prepare(
            'UPDATE discount_campaigns
             SET name = :name,
                 description = :description,
                 type = :type,
                 value = :value,
                 starts_at = :starts_at,
                 ends_at = :ends_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':type' => $data['type'],
            ':value' => $data['value'],
            ':starts_at' => $data['starts_at'],
            ':ends_at' => $data['ends_at'],
            ':id' => $id,
        ]);

        return [
            'success' => true,
            'message' => 'Campagna aggiornata correttamente.',
        ];
    }

    public function setStatus(int $id, bool $active): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la campagna.',
                'error' => 'Identificativo campagna non valido.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE discount_campaigns SET is_active = :active WHERE id = :id'
        );
        $stmt->execute([
            ':active' => $active ? 1 : 0,
            ':id' => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la campagna.',
                'error' => 'Campagna non trovata.',
            ];
        }

        return [
            'success' => true,
            'message' => $active ? 'Campagna attivata.' : 'Campagna disattivata.',
        ];
    }

    public function calculateDiscount(array $campaign, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $type = strtolower((string) ($campaign['type'] ?? 'fixed'));
        $value = (float) ($campaign['value'] ?? 0);
        $discount = 0.0;

        if ($type === 'percent') {
            $discount = $subtotal * ($value / 100);
        } else {
            $discount = $value;
        }

        if ($discount < 0) {
            $discount = 0.0;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return round($discount, 2);
    }

    private function normalizeDate(null|string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', trim($value));
        if ($date === false) {
            return null;
        }

        return $date->format('Y-m-d 00:00:00');
    }

    /**
     * @param array<string, mixed> $input
     * @return array{
     *     success:bool,
     *     error?:string,
     *     data?:array{
     *         name:string,
     *         type:string,
     *         value:float,
     *         description:?string,
     *         starts_at:?string,
     *         ends_at:?string
     *     }
     * }
     */
    private function normalizeCampaignInput(array $input): array
    {
        $name = trim((string) ($input['campaign_name'] ?? ''));
        $type = strtolower(trim((string) ($input['campaign_type'] ?? 'fixed')));
        $value = (float) ($input['campaign_value'] ?? 0);
        $description = trim((string) ($input['campaign_description'] ?? ''));
        $startsAt = $this->normalizeDate($input['campaign_starts_at'] ?? null);
        $endsAt = $this->normalizeDate($input['campaign_ends_at'] ?? null);

        if ($name === '') {
            return ['success' => false, 'error' => 'Il nome della campagna è obbligatorio.'];
        }

        if (!in_array($type, ['fixed', 'percent'], true)) {
            return ['success' => false, 'error' => 'Tipo di sconto non valido.'];
        }

        if ($value <= 0) {
            return ['success' => false, 'error' => 'Il valore dello sconto deve essere maggiore di zero.'];
        }

        if ($type === 'percent' && $value > 100) {
            return ['success' => false, 'error' => 'Una campagna percentuale non può superare il 100%.'];
        }

        if ($startsAt !== null && $endsAt !== null && $startsAt > $endsAt) {
            return ['success' => false, 'error' => 'La data di fine deve essere successiva alla data di inizio.'];
        }

        return [
            'success' => true,
            'data' => [
                'name' => $name,
                'type' => $type === 'percent' ? 'Percent' : 'Fixed',
                'value' => round($value, 2),
                'description' => $description !== '' ? $description : null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ],
        ];
    }
}
