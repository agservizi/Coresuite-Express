<?php
declare(strict_types=1);

use App\Services\Integrations\Coresuite\CoresuiteClient;
use App\Services\Integrations\DigitalSignature\DigitalSignatureClient;
use App\Services\Integrations\Http\HttpClient;
use App\Services\Integrations\IntegrationLogger;
use App\Services\Integrations\IntegrationService;
use App\Services\Integrations\Payments\PaymentGatewayClient;
use App\Services\Integrations\Ticketing\TicketingClient;

if (!function_exists('bootstrapIntegrationService')) {
    /**
     * @param array<string,mixed> $integrationsConfig
     */
    function bootstrapIntegrationService(array $integrationsConfig): IntegrationService
    {
        if (isset($GLOBALS['integrationService']) && $GLOBALS['integrationService'] instanceof IntegrationService) {
            return $GLOBALS['integrationService'];
        }

        $rootPath = dirname(__DIR__, 2);
        $logFile = $rootPath . '/storage/logs/integrations.log';
        $logger = new IntegrationLogger($logFile);

        $coresuiteClient = null;
        $coresuiteConfig = $integrationsConfig['coresuite'] ?? [];
        if (is_array($coresuiteConfig)) {
            $baseUrl = isset($coresuiteConfig['base_url']) ? trim((string) $coresuiteConfig['base_url']) : '';
            $apiKey = isset($coresuiteConfig['api_key']) ? trim((string) $coresuiteConfig['api_key']) : '';
            if ($baseUrl !== '' && $apiKey !== '') {
                $coresuiteHttp = new HttpClient($baseUrl, $logFile);
                $coresuiteClient = new CoresuiteClient(
                    $coresuiteHttp,
                    $logger,
                    $baseUrl,
                    $apiKey,
                    isset($coresuiteConfig['tenant']) ? (string) $coresuiteConfig['tenant'] : null,
                    isset($coresuiteConfig['webhook_secret']) ? (string) $coresuiteConfig['webhook_secret'] : null,
                    isset($coresuiteConfig['endpoints']) && is_array($coresuiteConfig['endpoints']) ? $coresuiteConfig['endpoints'] : []
                );
            }
        }

        $paymentGatewayClient = null;
        $paymentConfig = $integrationsConfig['payments'] ?? [];
        if (is_array($paymentConfig)) {
            $baseUrl = isset($paymentConfig['base_url']) ? trim((string) $paymentConfig['base_url']) : '';
            $apiKey = isset($paymentConfig['api_key']) ? trim((string) $paymentConfig['api_key']) : '';
            if ($baseUrl !== '' && $apiKey !== '') {
                $paymentHttp = new HttpClient($baseUrl, $logFile);
                $paymentGatewayClient = new PaymentGatewayClient(
                    $paymentHttp,
                    $logger,
                    $baseUrl,
                    $apiKey,
                    isset($paymentConfig['endpoints']) && is_array($paymentConfig['endpoints']) ? $paymentConfig['endpoints'] : []
                );
            }
        }

        $ticketingClient = null;
        $ticketingConfig = $integrationsConfig['ticketing'] ?? [];
        if (is_array($ticketingConfig)) {
            $baseUrl = isset($ticketingConfig['base_url']) ? trim((string) $ticketingConfig['base_url']) : '';
            $apiKey = isset($ticketingConfig['api_key']) ? trim((string) $ticketingConfig['api_key']) : '';
            if ($baseUrl !== '' && $apiKey !== '') {
                $ticketingHttp = new HttpClient($baseUrl, $logFile);
                $ticketingClient = new TicketingClient(
                    $ticketingHttp,
                    $logger,
                    $baseUrl,
                    $apiKey,
                    isset($ticketingConfig['account_id']) ? (string) $ticketingConfig['account_id'] : null,
                    isset($ticketingConfig['endpoints']) && is_array($ticketingConfig['endpoints']) ? $ticketingConfig['endpoints'] : []
                );
            }
        }

        $digitalSignatureClient = null;
        $signatureConfig = $integrationsConfig['digital_signature'] ?? [];
        if (is_array($signatureConfig)) {
            $baseUrl = isset($signatureConfig['base_url']) ? trim((string) $signatureConfig['base_url']) : '';
            $apiKey = isset($signatureConfig['api_key']) ? trim((string) $signatureConfig['api_key']) : '';
            if ($baseUrl !== '' && $apiKey !== '') {
                $signatureHttp = new HttpClient($baseUrl, $logFile);
                $digitalSignatureClient = new DigitalSignatureClient(
                    $signatureHttp,
                    $logger,
                    $baseUrl,
                    $apiKey,
                    isset($signatureConfig['endpoints']) && is_array($signatureConfig['endpoints']) ? $signatureConfig['endpoints'] : []
                );
            }
        }

        $integrationService = new IntegrationService(
            $coresuiteClient,
            $paymentGatewayClient,
            $ticketingClient,
            $digitalSignatureClient,
            $logger
        );

        $GLOBALS['integrationService'] = $integrationService;

        return $integrationService;
    }
}
