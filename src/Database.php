<?php
declare(strict_types=1);

namespace LiberaPIX;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function init(): void
    {
        if (self::$pdo) return;

        $path = Config::sqlitePath();
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $dsn = 'sqlite:' . $path;
        self::$pdo = new PDO($dsn);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) self::init();
        return self::$pdo;
    }

    public static function migrate(): void
    {
        $sql = file_get_contents(__DIR__ . '/../migrations/001_create_tables.sql');
        self::pdo()->exec($sql);
    }
}
