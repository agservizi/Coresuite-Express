<?php
declare(strict_types=1);

namespace App\Services\Integrations\DigitalSignature;

use App\Services\Integrations\Http\HttpClient;
use App\Services\Integrations\IntegrationLogger;

final class DigitalSignatureClient
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly IntegrationLogger $logger,
        private readonly ?string $baseUrl,
        private readonly ?string $apiKey,
        private readonly array $endpoints = []
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->baseUrl !== null && $this->baseUrl !== '' && $this->apiKey !== null && $this->apiKey !== '';
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function sendSignatureRequest(array $payload): array
    {
        $endpoint = $this->endpoints['send_request'] ?? '/api/v1/signature_requests';
        return $this->call('POST', $endpoint, $payload, 'signature_request');
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function cancelSignatureRequest(string $externalId, array $payload = []): array
    {
        $endpointTemplate = $this->endpoints['cancel'] ?? '/api/v1/signature_requests/{id}/cancel';
        $endpoint = str_replace('{id}', rawurlencode($externalId), $endpointTemplate);
        return $this->call('POST', $endpoint, $payload, 'signature_cancel');
    }

    public function fetchStatus(string $externalId): array
    {
        $endpointTemplate = $this->endpoints['status'] ?? '/api/v1/signature_requests/{id}';
        $endpoint = str_replace('{id}', rawurlencode($externalId), $endpointTemplate);
        return $this->call('GET', $endpoint, [], 'signature_status');
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function call(string $method, string $endpoint, array $payload, string $operation): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'status' => 0,
                'body' => null,
                'error' => 'digital_signature_disabled',
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];

        $body = $method === 'GET' ? null : $payload;

        $response = $this->http->request($method, $endpoint, [
            'base_url' => $this->baseUrl,
            'headers' => $headers,
            'body' => $body,
            'timeout' => 30,
        ]);

        $success = $response['status'] >= 200 && $response['status'] < 300;
        if ($success) {
            $this->logger->info('Digital signature call success', ['operation' => $operation, 'status' => $response['status']]);
        } else {
            $this->logger->error('Digital signature call failed', [
                'operation' => $operation,
                'status' => $response['status'],
                'error' => $response['error'] ?? null,
            ]);
        }

        return [
            'success' => $success,
            'status' => $response['status'],
            'body' => $response['body'],
            'error' => $response['error'] ?? null,
        ];
    }
}
