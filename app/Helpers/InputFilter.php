<?php
declare(strict_types=1);

namespace App\Helpers;

final class InputFilter
{
    /**
     * Normalises a scalar value into a trimmed string.
     */
    public static function string($value, int $maxLength = 255, bool $trim = true, bool $normalizeWhitespace = true): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $string = (string) $value;

        if ($trim) {
            $string = trim($string);
        }

        $string = self::stripControlChars($string, false);

        if ($normalizeWhitespace) {
            $string = preg_replace('/[ \t]+/', ' ', $string) ?? $string;
        }

        if ($maxLength > 0) {
            $string = self::limitLength($string, $maxLength);
        }

        return $string;
    }

    /**
     * Returns a lower-case, trimmed string.
     */
    public static function lowercase($value, int $maxLength = 255): string
    {
        $string = self::string($value, $maxLength, true, true);
        if ($string === '') {
            return '';
        }

        return function_exists('mb_strtolower') ? mb_strtolower($string) : strtolower($string);
    }

    /**
     * Returns a normalised email (lower-case) or null if invalid/missing.
     */
    public static function email($value): ?string
    {
        $string = self::string($value, 254, true, true);
        if ($string === '') {
            return null;
        }

        $normalized = function_exists('mb_strtolower') ? mb_strtolower($string) : strtolower($string);
        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }

    /**
     * Normalises checkbox-like values into booleans.
     */
    public static function bool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'on', 'yes', 'y'], true);
        }

        return false;
    }

    /**
     * Keeps only digits, enforcing optional min/max length constraints.
     */
    public static function digits($value, int $minLength = 0, ?int $maxLength = null): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($digits === '') {
            return null;
        }

        if ($maxLength !== null && $maxLength > 0 && strlen($digits) > $maxLength) {
            $digits = substr($digits, 0, $maxLength);
        }

        if ($minLength > 0 && strlen($digits) < $minLength) {
            return null;
        }

        return $digits;
    }

    /**
     * Sanitises multi-line text preserving new lines.
     */
    public static function multiline($value, int $maxLength = 2000): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $string = (string) $value;
        $string = str_replace(["\r\n", "\r"], "\n", $string);
        $string = self::stripControlChars($string, true);
        $string = trim($string);

        if ($maxLength > 0) {
            $string = self::limitLength($string, $maxLength);
        }

        return $string;
    }

    /**
     * Returns a cleaned list of non-empty strings.
     *
     * @param mixed $value
     * @return array<int, string>
     */
    public static function arrayOfStrings($value, int $maxLength = 255): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $string = self::string($item, $maxLength, true, true);
            if ($string !== '') {
                $result[] = $string;
            }
        }

        return $result;
    }

    /**
     * Casts any value to int, clamped between min and max bounds.
     */
    public static function int($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX, int $default = 0): int
    {
        if (is_int($value)) {
            $int = $value;
        } elseif (is_numeric($value)) {
            $int = (int) $value;
        } elseif (is_string($value)) {
            $filtered = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            $int = is_string($filtered) && $filtered !== '' ? (int) $filtered : $default;
        } else {
            $int = $default;
        }

        if ($int < $min) {
            return $min;
        }

        if ($int > $max) {
            return $max;
        }

        return $int;
    }

    private static function limitLength(string $value, int $maxLength): string
    {
        if ($maxLength <= 0) {
            return $value;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength);
    }

    private static function stripControlChars(string $value, bool $allowNewLines): string
    {
        $pattern = $allowNewLines ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/' : '/[\x00-\x1F\x7F]+/';
        return preg_replace($pattern, '', $value) ?? $value;
    }
}
