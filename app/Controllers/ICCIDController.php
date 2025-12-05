<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\ICCIDService;

final class ICCIDController
{
    public function __construct(private ICCIDService $iccidService)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?string $status = null): array
    {
        return $this->iccidService->listStock($status);
    }

    /**
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   pagination: array{page:int, per_page:int, total:int, pages:int}
     * }
     */
    public function listPaginated(int $page, int $perPage, ?string $status = null): array
    {
        return $this->iccidService->paginateStock($page, $perPage, $status);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function providers(): array
    {
        return $this->iccidService->listProviders();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function available(): array
    {
        return $this->iccidService->listAvailable();
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, error?:string}
     */
    public function create(array $input): array
    {
        $providerId = (int) ($input['provider_id'] ?? 0);
        if ($providerId <= 0) {
            return [
                'success' => false,
                'message' => 'Seleziona un operatore valido.',
                'error' => 'Provider non valido',
            ];
        }

        $iccid = (string) ($input['iccid'] ?? '');
        $notes = array_key_exists('notes', $input) && $input['notes'] !== null ? (string) $input['notes'] : null;

        return $this->iccidService->addSim($iccid, $providerId, $notes);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, error?:string, errors?:array<int, string>, inserted?:int, failed?:int}
     */
    public function createBulk(array $input): array
    {
        $providerId = (int) ($input['provider_id'] ?? 0);
        if ($providerId <= 0) {
            return [
                'success' => false,
                'message' => 'Seleziona un operatore valido.',
                'error' => 'Provider non valido',
            ];
        }

        $rawList = isset($input['bulk_iccids']) ? (string) $input['bulk_iccids'] : '';
        $lines = preg_split('/\r\n|\r|\n/', $rawList) ?: [];

        $items = [];
        foreach ($lines as $index => $line) {
            $cleanLine = trim($line);
            if ($cleanLine === '') {
                continue;
            }

            $parts = array_map('trim', explode(';', $cleanLine, 2));
            $iccid = $parts[0];
            $note = $parts[1] ?? null;
            if ($note !== null && $note === '') {
                $note = null;
            }

            $items[] = [
                'iccid' => $iccid,
                'notes' => $note,
                'line' => $index + 1,
            ];
        }

        if ($items === []) {
            return [
                'success' => false,
                'message' => 'Inserisci almeno una SIM prima di procedere.',
                'error' => 'Lista SIM vuota',
            ];
        }

        return $this->iccidService->addBulkSims($providerId, $items);
    }
}
