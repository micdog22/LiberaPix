<?php
declare(strict_types=1);

namespace LiberaPIX;

final class TokenService
{
    public static function generate(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes)); // 32 bytes = 64 hex chars
    }

    public static function expirationFromNow(int $hours): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('+' . $hours . ' hours');
    }

    public static function isExpired(?string $iso8601): bool
    {
        if (!$iso8601) return true;
        $exp = new \DateTimeImmutable($iso8601);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return $now > $exp;
    }
}
