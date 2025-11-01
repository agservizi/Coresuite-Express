<?php
declare(strict_types=1);

namespace App\Services\Integrations\Http;

use RuntimeException;

final class HttpClient
{
    public function __construct(private readonly ?string $defaultBaseUrl = null, private readonly ?string $logFile = null)
    {
    }

    /**
     * @param array{
     *     base_url?:string|null,
     *     headers?:array<string,string>,
     *     query?:array<string,string|int|float|null>,
     *     body?:array<mixed>|string|null,
     *     timeout?:int|float|null,
     *     retries?:int|null,
     *     retry_delay_ms?:int|null
     * } $options
     * @return array{status:int, headers:array<string,string>, body:mixed, error?:string}
     */
    public function request(string $method, string $uri, array $options = []): array
    {
        $baseUrl = $options['base_url'] ?? $this->defaultBaseUrl ?? '';
        $url = $this->buildUrl($baseUrl, $uri, $options['query'] ?? []);

        $headers = $options['headers'] ?? [];
        $payload = $options['body'] ?? null;
        $timeout = $options['timeout'] ?? 15;
        $retries = max(0, (int) ($options['retries'] ?? 1));
        $retryDelayMs = max(0, (int) ($options['retry_delay_ms'] ?? 250));

        $normalizedHeaders = [];
        foreach ($headers as $name => $value) {
            $trimmedName = trim((string) $name);
            if ($trimmedName === '') {
                continue;
            }

            $normalizedHeaders[$trimmedName] = trim((string) $value);
        }

        if (is_array($payload)) {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($body === false) {
                throw new RuntimeException('Impossibile serializzare il payload JSON.');
            }
            if (!isset($normalizedHeaders['Content-Type'])) {
                $normalizedHeaders['Content-Type'] = 'application/json';
            }
        } elseif (is_string($payload)) {
            $body = $payload;
        } elseif ($payload === null) {
            $body = null;
        } else {
            $body = (string) $payload;
        }

        $attempt = 0;
        $lastError = null;
        $response = null;

        while ($attempt <= $retries) {
            $attempt++;
            $response = $this->performRequest($method, $url, $normalizedHeaders, $body, (float) $timeout);
            if (!isset($response['error'])) {
                break;
            }
            $lastError = $response['error'];
            if ($attempt <= $retries) {
                usleep($retryDelayMs * 1000);
            }
        }

        if ($response === null) {
            $response = [
                'status' => 0,
                'headers' => [],
                'body' => null,
                'error' => $lastError ?? 'richiesta non eseguita',
            ];
        }

        if ($this->logFile !== null) {
            $this->appendLog($method, $url, $response['status'], $response['error'] ?? null);
        }

        return $response;
    }

    /**
     * @param array<string,string> $headers
     * @return array{status:int, headers:array<string,string>, body:mixed, error?:string}
     */
    private function performRequest(string $method, string $url, array $headers, ?string $body, float $timeout): array
    {
        $handle = curl_init($url);
        if ($handle === false) {
            return [
                'status' => 0,
                'headers' => [],
                'body' => null,
                'error' => 'inizializzazione curl fallita',
            ];
        }

        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($handle, CURLOPT_TIMEOUT, max(0.5, $timeout));
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, max(0.5, min($timeout, 5.0)));
        curl_setopt($handle, CURLOPT_HEADER, true);

        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        if ($headers !== []) {
            $headerLines = [];
            foreach ($headers as $name => $value) {
                $headerLines[] = $name . ': ' . $value;
            }
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headerLines);
        }

        $rawResponse = curl_exec($handle);
        if ($rawResponse === false) {
            $error = curl_error($handle) ?: 'errore sconosciuto';
            curl_close($handle);

            return [
                'status' => 0,
                'headers' => [],
                'body' => null,
                'error' => $error,
            ];
        }

        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE) ?: 0;
        $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE) ?: 0;
        curl_close($handle);

        $rawHeaders = substr($rawResponse, 0, $headerSize);
        $rawBody = substr($rawResponse, $headerSize);

        $decodedBody = $this->decodeBody($rawBody);
        $parsedHeaders = $this->parseHeaders($rawHeaders);

        return [
            'status' => $status,
            'headers' => $parsedHeaders,
            'body' => $decodedBody,
        ];
    }

    private function buildUrl(string $baseUrl, string $uri, array $query): string
    {
        $trimmedBase = rtrim($baseUrl, '/');
        $trimmedUri = ltrim($uri, '/');
        $url = $trimmedBase !== '' ? $trimmedBase . '/' . $trimmedUri : $trimmedUri;

        if ($query !== []) {
            $parts = [];
            foreach ($query as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
            }
            if ($parts !== []) {
                $url .= '?' . implode('&', $parts);
            }
        }

        return $url;
    }

    /**
     * @return array<string,string>
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $lines = preg_split("/(\r\n|\r|\n)+/", trim($rawHeaders)) ?: [];
        $result = [];
        foreach ($lines as $line) {
            if ($line === '' || str_contains($line, 'HTTP/')) {
                continue;
            }
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $result[trim($name)] = trim($value);
        }

        return $result;
    }

    private function decodeBody(string $rawBody)
    {
        $trimmed = trim($rawBody);
        if ($trimmed === '') {
            return null;
        }

        $first = $trimmed[0];
        if ($first === '{' || $first === '[') {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $rawBody;
    }

    private function appendLog(string $method, string $url, int $status, ?string $error): void
    {
        $line = date('c') . ' | ' . strtoupper($method) . ' ' . $url . ' | ' . $status;
        if ($error !== null) {
            $line .= ' | error=' . $error;
        }
        $line .= PHP_EOL;

        $logFile = $this->logFile;
        if ($logFile === null) {
            return;
        }

        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
