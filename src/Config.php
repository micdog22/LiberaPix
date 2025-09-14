<?php
declare(strict_types=1);

namespace LiberaPIX;

final class Config
{
    public static function appUrl(): string
    {
        return rtrim($_ENV['APP_URL'] ?? 'http://localhost:8080', '/');
    }

    public static function sqlitePath(): string
    {
        return $_ENV['SQLITE_PATH'] ?? __DIR__ . '/../storage/database.sqlite';
    }

    public static function mpAccessToken(): string
    {
        $t = trim($_ENV['MP_ACCESS_TOKEN'] ?? '');
        if ($t === '') {
            throw new \RuntimeException('Defina MP_ACCESS_TOKEN no .env');
        }
        return $t;
    }

    public static function tokenTtlHours(): int
    {
        return (int)($_ENV['DOWNLOAD_TOKEN_TTL_HOURS'] ?? 24);
    }

    public static function supportEmail(): string
    {
        return $_ENV['SUPPORT_EMAIL'] ?? 'suporte@exemplo.com';
    }
}
