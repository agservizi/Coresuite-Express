<?php
declare(strict_types=1);

namespace App\Helpers;

final class EnvWriter
{
    public function __construct(private readonly string $path)
    {
    }

    /**
     * @param array<string, string> $values
     */
    public function setValues(array $values): bool
    {
        if ($values === []) {
            return true;
        }

        $directory = dirname($this->path);
        if (!is_dir($directory)) {
            return false;
        }

        $contents = '';
        if (is_file($this->path)) {
            $read = file_get_contents($this->path);
            if ($read === false) {
                return false;
            }
            $contents = $read;
        }

        $lineEnding = $this->detectLineEnding($contents);
        foreach ($values as $key => $value) {
            $normalizedLine = $key . '=' . $this->normalizeValue($value);
            $pattern = '/^' . preg_quote($key, '/') . '\s*=.*$/m';
            if (preg_match($pattern, $contents) === 1) {
                $updated = preg_replace($pattern, $normalizedLine, $contents);
                if ($updated === null) {
                    return false;
                }
                $contents = $updated;
            } else {
                $contents = rtrim($contents);
                if ($contents !== '') {
                    $contents .= $lineEnding;
                }
                $contents .= $normalizedLine . $lineEnding;
            }
        }

        if (!str_ends_with($contents, $lineEnding)) {
            $contents .= $lineEnding;
        }

        return file_put_contents($this->path, $contents) !== false;
    }

    private function normalizeValue(string $value): string
    {
        if ($value === '') {
            return "\"\"";
        }

        $needsQuoting = strpbrk($value, " \t#\"'\r\n") !== false;
        if (!$needsQuoting) {
            return $value;
        }

        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        return '"' . addcslashes($value, "\"\\") . '"';
    }

    private function detectLineEnding(string $contents): string
    {
        if ($contents === '') {
            return PHP_EOL;
        }

        if (str_contains($contents, "\r\n")) {
            return "\r\n";
        }

        if (str_contains($contents, "\r")) {
            return "\r";
        }

        return "\n";
    }

    public function isWritable(): bool
    {
        if (is_file($this->path)) {
            return is_writable($this->path);
        }

        return is_writable(dirname($this->path));
    }
}
