<?php

declare(strict_types=1);

namespace App\Core;

final class Url
{
    public static function root(string $path = ''): string
    {
        return self::basePath() . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }

    /** @param array<string, scalar> $query */
    public static function route(string $path, array $query = []): string
    {
        $url = self::root(trim($path, '/'));
        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }

    private static function basePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $publicPosition = strpos($script, '/public/');
        if ($publicPosition !== false) {
            return substr($script, 0, $publicPosition);
        }

        $directory = rtrim(str_replace('\\', '/', dirname($script)), '/.');
        return $directory === '' ? '' : $directory;
    }
}
