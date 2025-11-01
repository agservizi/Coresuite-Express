<?php
declare(strict_types=1);

namespace App\Services\Integrations\Coresuite;

use App\Services\Integrations\Http\HttpClient;
use App\Services\Integrations\IntegrationLogger;
use RuntimeException;

final class CoresuiteClient
{
    private const DEFAULT_TIMEOUT = 20;

    public function __construct(
        private readonly HttpClient $http,
        private readonly IntegrationLogger $logger,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly ?string $tenant = null,
        private readonly ?string $webhookSecret = null,
        private readonly array $endpoints = []
    ) {
        if ($this->baseUrl === '') {
            throw new RuntimeException('Base URL integrazione coresuite non configurata.');
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool, status:int, body:mixed, error?:string}
     */
    public function upsertCustomer(array $payload): array
    {
        $endpoint = $this->endpoints['customers'] ?? '/api/integrations/customers';
        return $this->send('PUT', $endpoint, $payload, ['operation' => 'customer_upsert']);
    }

    public function deleteCustomer(string $externalId): array
    {
        $endpoint = $this->endpoints['customer_delete'] ?? '/api/integrations/customers/{id}';
        $uri = str_replace('{id}', rawurlencode($externalId), $endpoint);
        return $this->send('DELETE', $uri, null, ['operation' => 'customer_delete']);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool, status:int, body:mixed, error?:string}
     */
    public function upsertProduct(array $payload): array
    {
        $endpoint = $this->endpoints['products'] ?? '/api/integrations/products';
        return $this->send('PUT', $endpoint, $payload, ['operation' => 'product_upsert']);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool, status:int, body:mixed, error?:string}
     */
    public function pushSale(array $payload): array
    {
        $endpoint = $this->endpoints['sales'] ?? '/api/integrations/sales';
        return $this->send('POST', $endpoint, $payload, ['operation' => 'sale_push']);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool, status:int, body:mixed, error?:string}
     */
    public function pushInventoryAdjustment(array $payload): array
    {
        $endpoint = $this->endpoints['inventory'] ?? '/api/integrations/inventory';
        return $this->send('POST', $endpoint, $payload, ['operation' => 'inventory_push']);
    }

    /**
     * @param array<string,mixed>|null $body
     * @param array<string,mixed> $meta
     * @return array{success:bool, status:int, body:mixed, error?:string}
     */
    private function send(string $method, string $endpoint, $body, array $meta): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => 'Coresuite-Express-Integrator/1.0',
        ];

        if ($this->tenant !== null) {
            $headers['X-Coresuite-Tenant'] = $this->tenant;
        }
        if ($this->webhookSecret !== null) {
            $headers['X-Integration-Secret'] = $this->webhookSecret;
        }

        $response = $this->http->request($method, $endpoint, [
            'base_url' => $this->baseUrl,
            'headers' => $headers,
            'body' => $body,
            'timeout' => self::DEFAULT_TIMEOUT,
            'retries' => 2,
            'retry_delay_ms' => 500,
        ]);

        $success = $response['status'] >= 200 && $response['status'] < 300;
        if ($success) {
            $this->logger->info('Coresuite request completed', $meta + ['status' => $response['status']]);
        } else {
            $this->logger->error('Coresuite request failed', $meta + [
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
