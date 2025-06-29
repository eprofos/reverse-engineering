<?php

declare(strict_types=1);

use App\Service\MySQLTypeMapper;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Enregistrer les types MySQL personnalisés dès le bootstrap
MySQLTypeMapper::registerCustomTypes();

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

// Load Docker configuration if available
if (file_exists(dirname(__DIR__) . '/.env.docker')) {
    (new Dotenv())->load(dirname(__DIR__) . '/.env.docker');
}

// Test-specific configuration
if (isset($_SERVER['APP_DEBUG']) && $_SERVER['APP_DEBUG']) {
    umask(0o000);
}

// Configuration for Docker tests
if (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'test') {
    // Augmenter les limites pour les tests d'intégration
    ini_set('memory_limit', $_ENV['PHPUNIT_MEMORY_LIMIT'] ?? '256M');
    ini_set('max_execution_time', '300');

    // Timezone configuration
    if (isset($_ENV['PHP_TIMEZONE'])) {
        date_default_timezone_set($_ENV['PHP_TIMEZONE']);
    }
}
