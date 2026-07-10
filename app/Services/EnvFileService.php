<?php

namespace App\Services;

use RuntimeException;

class EnvFileService
{
    public function update(array $values): void
    {
        $path = base_path('.env');

        if (! file_exists($path) || ! is_writable($path)) {
            throw new RuntimeException('.env file is missing or not writable.');
        }

        $contents = file_get_contents($path);

        foreach ($values as $key => $value) {
            if ($value === null) {
                continue;
            }

            $line = $key.'='.$this->formatValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $line, $contents);
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($path, $contents);
    }

    private function formatValue(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9_\-\.\/:@]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\"'], $value).'"';
    }
}
