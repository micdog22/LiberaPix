<?php
declare(strict_types=1);

namespace LiberaPIX;

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use LiberaPIX\Database;

// CLI helper for migrations: php src/bootstrap.php --migrate
if (php_sapi_name() === 'cli') {
    $argv = $_SERVER['argv'] ?? [];
    if (in_array('--migrate', $argv, true)) {
        Bootstrap::init();
        Database::migrate();
        echo "Migrations executed.\n";
        exit(0);
    }
}

final class Bootstrap
{
    public static function init(): void
    {
        // Load .env
        $root = dirname(__DIR__);
        if (file_exists($root . '/.env')) {
            $dotenv = Dotenv::createImmutable($root);
            $dotenv->load();
        }

        // Ensure storage dirs
        foreach (['storage','storage/logs','storage/products'] as $dir) {
            if (!is_dir($root . '/' . $dir)) {
                @mkdir($root . '/' . $dir, 0775, true);
            }
        }

        // Initialize DB (creates file if not exists)
        Database::init();
    }
}

Bootstrap::init();
