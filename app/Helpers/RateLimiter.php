<?php

declare(strict_types=1);

namespace App\Helpers;

final class RateLimiter
{
    private const STORAGE_FILE = 'rate_limits.json';

    public static function hit(string $key, int $decaySeconds): void
    {
        if ($key === '' || $decaySeconds <= 0) {
            return;
        }

        $state = self::loadState();
        $now = time();
        $record = $state[$key] ?? ['attempts' => 0, 'expires_at' => $now + $decaySeconds];

        if (!isset($record['expires_at']) || (int) $record['expires_at'] <= $now) {
            $record = ['attempts' => 0, 'expires_at' => $now + $decaySeconds];
        }

        $record['attempts'] = (int) ($record['attempts'] ?? 0) + 1;
        $record['expires_at'] = $now + $decaySeconds;
        $state[$key] = $record;

        self::storeState($state);
    }

    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        if ($key === '' || $maxAttempts <= 0 || $decaySeconds <= 0) {
            return false;
        }

        $state = self::loadState();
        $record = $state[$key] ?? null;
        if ($record === null) {
            return false;
        }

        $now = time();
        if (($record['expires_at'] ?? 0) <= $now) {
            unset($state[$key]);
            self::storeState($state);
            return false;
        }

        return (int) ($record['attempts'] ?? 0) >= $maxAttempts;
    }

    public static function availableIn(string $key): int
    {
        if ($key === '') {
            return 0;
        }

        $record = self::loadState()[$key] ?? null;
        if ($record === null) {
            return 0;
        }

        $remaining = (int) ($record['expires_at'] ?? 0) - time();
        return $remaining > 0 ? $remaining : 0;
    }

    public static function clear(string $key): void
    {
        if ($key === '') {
            return;
        }

        $state = self::loadState();
        if (!isset($state[$key])) {
            return;
        }

        unset($state[$key]);
        self::storeState($state);
    }

    /**
     * @return array<string, array{attempts:int, expires_at:int}>
     */
    private static function loadState(): array
    {
        $path = self::storagePath();
        if (!is_file($path)) {
            return [];
        }

        $json = @file_get_contents($path);
        if ($json === false || $json === '') {
            return [];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }

        $now = time();
        $changed = false;
        foreach ($data as $key => $record) {
            if (!is_array($record)) {
                unset($data[$key]);
                $changed = true;
                continue;
            }
            if (($record['expires_at'] ?? 0) <= $now) {
                unset($data[$key]);
                $changed = true;
            }
        }

        if ($changed) {
            self::storeState($data);
        }

        return $data;
    }

    /**
     * @param array<string, array{attempts:int, expires_at:int}> $state
     */
    private static function storeState(array $state): void
    {
        $path = self::storagePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $json = json_encode($state, JSON_PRETTY_PRINT);
        if ($json === false) {
            return;
        }

        @file_put_contents($path, $json, LOCK_EX);
    }

    private static function storagePath(): string
    {
        $root = dirname(__DIR__, 2);
        return $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . self::STORAGE_FILE;
    }
}
