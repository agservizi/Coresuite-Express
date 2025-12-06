<?php

declare(strict_types=1);

namespace App\Helpers;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const FIELD_NAME = '_token';
    private const HEADER_CANDIDATES = [
        'HTTP_X_CSRF_TOKEN',
        'HTTP_X_XSRF_TOKEN',
        'HTTP_CSRF_TOKEN',
    ];

    public static function token(): string
    {
        self::ensureSession();
        $token = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($token) || $token === '') {
            $token = self::generateToken();
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    public static function inputField(): string
    {
        $value = htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . $value . '">';
    }

    public static function validateRequest(?array $postData = null, ?array $serverData = null): bool
    {
        self::ensureSession();
        $postData ??= $_POST;
        $serverData ??= $_SERVER;

        $provided = self::extractToken($postData, $serverData);
        if ($provided === null) {
            return false;
        }

        $known = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($known) || $known === '') {
            return false;
        }

        return hash_equals($known, $provided);
    }

    public static function injectIntoForms(?string $html): string
    {
        if ($html === null || $html === '' || stripos($html, '<form') === false) {
            return $html ?? '';
        }

        $fieldMarkup = self::inputField();

        $rewritten = preg_replace_callback('/<form\b[^>]*>/i', static function (array $matches) use ($fieldMarkup): string {
            $tag = $matches[0];
            if (stripos($tag, 'data-csrf-skip') !== false || stripos($tag, 'data-csrf-protected') !== false) {
                return $tag;
            }

            if (preg_match('/\bmethod\s*=\s*(["\']?)post\\1/i', $tag) !== 1) {
                return $tag;
            }

            $tagWithFlag = preg_replace('/>$/', ' data-csrf-protected="1">', $tag, 1);
            if ($tagWithFlag === null) {
                $tagWithFlag = $tag;
            }

            return $tagWithFlag . "\n    " . $fieldMarkup;
        }, $html);

        return is_string($rewritten) ? $rewritten : $html;
    }

    private static function extractToken(?array $postData, ?array $serverData): ?string
    {
        if (is_array($postData) && array_key_exists(self::FIELD_NAME, $postData)) {
            $value = $postData[self::FIELD_NAME];
            if (is_array($value)) {
                $value = reset($value);
            }
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        if (is_array($serverData)) {
            foreach (self::HEADER_CANDIDATES as $header) {
                if (!empty($serverData[$header]) && is_string($serverData[$header])) {
                    $candidate = trim($serverData[$header]);
                    if ($candidate !== '') {
                        return $candidate;
                    }
                }
            }
        }

        $headerList = function_exists('getallheaders') ? @getallheaders() : null;
        if (is_array($headerList)) {
            foreach ($headerList as $name => $value) {
                if (!is_string($name)) {
                    continue;
                }
                $normalized = strtoupper(str_replace('-', '_', $name));
                if (!in_array('HTTP_' . $normalized, self::HEADER_CANDIDATES, true)) {
                    continue;
                }
                if (is_array($value)) {
                    $value = reset($value);
                }
                if (is_string($value)) {
                    $candidate = trim($value);
                    if ($candidate !== '') {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private static function generateToken(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (\Throwable) {
            try {
                return bin2hex(random_bytes(16));
            } catch (\Throwable) {
                return sha1(uniqid('csrf', true));
            }
        }
    }
}
