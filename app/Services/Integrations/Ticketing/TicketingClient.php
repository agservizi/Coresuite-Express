<?php
declare(strict_types=1);

namespace App\Services\Integrations\Ticketing;

use App\Services\Integrations\Http\HttpClient;
use App\Services\Integrations\IntegrationLogger;

final class TicketingClient
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly IntegrationLogger $logger,
        private readonly ?string $baseUrl,
        private readonly ?string $apiKey,
        private readonly ?string $accountId = null,
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
    public function createTicket(array $payload): array
    {
        $endpoint = $this->endpoints['create'] ?? '/api/v1/tickets';
        return $this->call('POST', $endpoint, $payload, 'ticket_create');
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function updateTicket(string $externalId, array $payload): array
    {
        $endpointTemplate = $this->endpoints['update'] ?? '/api/v1/tickets/{id}';
        $endpoint = str_replace('{id}', rawurlencode($externalId), $endpointTemplate);
        return $this->call('PUT', $endpoint, $payload, 'ticket_update');
    }

    public function addComment(string $externalId, string $comment, bool $public = true): array
    {
        $endpointTemplate = $this->endpoints['comment'] ?? '/api/v1/tickets/{id}/comments';
        $endpoint = str_replace('{id}', rawurlencode($externalId), $endpointTemplate);

        return $this->call('POST', $endpoint, [
            'comment' => $comment,
            'is_public' => $public,
        ], 'ticket_comment');
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
                'error' => 'ticketing_disabled',
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];

        if ($this->accountId !== null && $this->accountId !== '') {
            $headers['X-Account-Id'] = $this->accountId;
        }

        $response = $this->http->request($method, $endpoint, [
            'base_url' => $this->baseUrl,
            'headers' => $headers,
            'body' => $payload,
            'timeout' => 20,
        ]);

        $success = $response['status'] >= 200 && $response['status'] < 300;
        if ($success) {
            $this->logger->info('Ticketing call success', ['operation' => $operation, 'status' => $response['status']]);
        } else {
            $this->logger->error('Ticketing call failed', [
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
