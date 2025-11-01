<?php
declare(strict_types=1);

namespace App\Services\Integrations\Payments;

use App\Services\Integrations\Http\HttpClient;
use App\Services\Integrations\IntegrationLogger;

final class PaymentGatewayClient
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
    public function createPaymentIntent(array $payload): array
    {
        $endpoint = $this->endpoints['create_intent'] ?? '/v1/payment_intents';
        return $this->call('POST', $endpoint, $payload, 'payment_intent_create');
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function capturePayment(array $payload): array
    {
        $endpoint = $this->endpoints['capture'] ?? '/v1/payments/capture';
        return $this->call('POST', $endpoint, $payload, 'payment_capture');
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function refund(array $payload): array
    {
        $endpoint = $this->endpoints['refund'] ?? '/v1/refunds';
        return $this->call('POST', $endpoint, $payload, 'payment_refund');
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
                'error' => 'payment_gateway_disabled',
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];

        $response = $this->http->request($method, $endpoint, [
            'base_url' => $this->baseUrl,
            'headers' => $headers,
            'body' => $payload,
            'timeout' => 20,
        ]);

        $success = $response['status'] >= 200 && $response['status'] < 300;
        if ($success) {
            $this->logger->info('Payment gateway call success', ['operation' => $operation, 'status' => $response['status']]);
        } else {
            $this->logger->error('Payment gateway call failed', [
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
