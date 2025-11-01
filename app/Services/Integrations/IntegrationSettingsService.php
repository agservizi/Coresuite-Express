<?php
declare(strict_types=1);

namespace App\Services\Integrations;

use App\Helpers\EnvWriter;
use PDO;
use Throwable;

final class IntegrationSettingsService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly EnvWriter $envWriter
    ) {
    }

    /**
     * @return array{success:bool,message:string,error?:string,api_key?:string}
     */
    public function generateCoresuiteApiKey(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Utente non valido.',
                'error' => 'Impossibile registrare l\'operazione senza un utente autenticato.',
            ];
        }

        if (!$this->envWriter->isWritable()) {
            return [
                'success' => false,
                'message' => 'File .env non scrivibile.',
                'error' => 'Aggiorna i permessi del file .env per ruotare la chiave.',
            ];
        }

        try {
            $newKey = bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Generazione della chiave fallita.',
                'error' => $exception->getMessage(),
            ];
        }

        $updateResult = $this->envWriter->setValues(['CORESUITE_API_KEY' => $newKey]);
        if (!$updateResult) {
            return [
                'success' => false,
                'message' => 'Aggiornamento della chiave non riuscito.',
                'error' => 'Salvataggio su .env fallito: controlla i permessi e riprova.',
            ];
        }

        $this->recordAudit($userId, $newKey);

        return [
            'success' => true,
            'message' => 'Nuova API key generata. Aggiorna business.coresuite.it con il nuovo valore.',
            'api_key' => $newKey,
        ];
    }

    public function canUpdateEnv(): bool
    {
        return $this->envWriter->isWritable();
    }

    private function recordAudit(int $userId, string $newKey): void
    {
        try {
            $masked = substr($newKey, 0, 4) . '...' . substr($newKey, -4);
            $stmt = $this->pdo->prepare(
                'INSERT INTO audit_log (user_id, action, description) VALUES (:user_id, :action, :description)'
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':action' => 'integration_key_rotated',
                ':description' => 'Nuova API key business.coresuite.it generata (' . $masked . ')',
            ]);
        } catch (Throwable) {
            // L'audit non deve bloccare la rotazione della chiave.
        }
    }
}
