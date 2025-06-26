#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\DatabaseAnalyzer;
use App\Service\EntityGenerator;
use App\Service\FileWriter;
use App\Service\MetadataExtractor;
use App\Service\ReverseEngineeringService;
use Doctrine\DBAL\DriverManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Entity generation script for Docker environment.
 * Uses PHP services directly like in integration tests.
 */

// Default configuration
$defaultConfig = [
    'host'       => 'mysql',
    'port'       => 3306,
    'dbname'     => 'sakila',
    'user'       => 'sakila_user',
    'password'   => 'sakila_password',
    'charset'    => 'utf8mb4',
    'namespace'  => 'Sakila\\Entity',
    'output_dir' => 'generated/sakila',
    'force'      => false,
    'dry_run'    => false,
];

// Parsing des arguments
$options = parseArguments($argv, $defaultConfig);

try {
    echo "🚀 Generating entities from Sakila...\n";
    echo "📊 Configuration:\n";
    echo "   - Database: {$options['host']}:{$options['port']}/{$options['dbname']}\n";
    echo "   - Namespace: {$options['namespace']}\n";
    echo "   - Répertoire de sortie: {$options['output_dir']}\n";
    echo '   - Mode dry-run: ' . ($options['dry_run'] ? 'Oui' : 'Non') . "\n";
    echo '   - Force: ' . ($options['force'] ? 'Oui' : 'Non') . "\n\n";

    // Créer le répertoire de sortie
    if (! $options['dry_run'] && ! is_dir($options['output_dir'])) {
        mkdir($options['output_dir'], 0o755, true);
        echo "📁 Répertoire créé: {$options['output_dir']}\n";
    }

    // Database configuration
    $databaseConfig = [
        'driver'   => 'pdo_mysql',
        'host'     => $options['host'],
        'port'     => $options['port'],
        'dbname'   => $options['dbname'],
        'user'     => $options['user'],
        'password' => $options['password'],
        'charset'  => $options['charset'],
    ];

    // Create connection
    echo "🔌 Connecting to database...\n";
    $connection = DriverManager::getConnection($databaseConfig);
    $connection->connect();
    echo "✅ Connection established successfully\n\n";

    // Configurer les services
    $databaseAnalyzer  = new DatabaseAnalyzer($databaseConfig, $connection);
    $metadataExtractor = new MetadataExtractor($databaseAnalyzer);

    // Configurer Twig
    $loader = new FilesystemLoader(__DIR__ . '/../src/Resources/templates');
    $twig   = new Environment($loader);

    $entityGenerator = new EntityGenerator($twig);
    $fileWriter      = new FileWriter(__DIR__ . '/..');

    $service = new ReverseEngineeringService(
        $databaseAnalyzer,
        $metadataExtractor,
        $entityGenerator,
        $fileWriter,
    );

    // Validate connection
    if (! $service->validateDatabaseConnection()) {
        throw new Exception('Unable to validate database connection');
    }

    // Obtenir les tables disponibles
    $availableTables = $service->getAvailableTables();
    echo '📋 Tables disponibles: ' . count($availableTables) . "\n";

    foreach ($availableTables as $table) {
        echo "   - {$table}\n";
    }
    echo "\n";

    // Generate entities
    echo "⚙️  Generating entities...\n";
    $startTime = microtime(true);

    $result = $service->generateEntities([
        'output_dir' => $options['output_dir'],
        'namespace'  => $options['namespace'],
        'force'      => $options['force'],
        'dry_run'    => $options['dry_run'],
    ]);

    $endTime       = microtime(true);
    $executionTime = round($endTime - $startTime, 3);

    // Display results
    echo "\n🎉 Generation completed successfully!\n";
    echo "📊 Statistics:\n";
    echo "   - Tables processed: {$result['tables_processed']}\n";
    echo '   - Entities generated: ' . count($result['entities']) . "\n";
    echo '   - Files created: ' . count($result['files']) . "\n";
    echo "   - Execution time: {$executionTime}s\n\n";

    if ($options['dry_run']) {
        echo "🔍 Dry-run mode - Preview of entities that would be generated:\n";

        foreach ($result['entities'] as $entity) {
            echo "   - {$entity['name']} (table: {$entity['table']}, namespace: {$entity['namespace']})\n";
        }
    } else {
        echo "📁 Generated files:\n";

        foreach ($result['files'] as $file) {
            echo "   - {$file}\n";
        }
    }

    echo "\n✅ Entity generation completed successfully!\n";
} catch (Exception $e) {
    echo "\n❌ Error during generation: {$e->getMessage()}\n";
    echo "📍 File: {$e->getFile()}:{$e->getLine()}\n";

    if (isset($argv) && in_array('--verbose', $argv, true)) {
        echo "\n🔍 Trace complète:\n";
        echo $e->getTraceAsString() . "\n";
    }

    exit(1);
}

/**
 * Parse les arguments de ligne de commande.
 */
function parseArguments(array $argv, array $defaultConfig): array
{
    $options = $defaultConfig;

    for ($i = 1; $i < count($argv); ++$i) {
        $arg = $argv[$i];

        // Gérer les arguments avec = (ex: --namespace="App\Entity")
        if (str_contains($arg, '=')) {
            [$key, $value] = explode('=', $arg, 2);
            $arg           = $key;
            $nextValue     = $value;
        } else {
            $nextValue = $argv[$i + 1] ?? null;
        }

        switch ($arg) {
            case '--namespace':
                if (isset($nextValue)) {
                    $options['namespace'] = $nextValue;

                    if (! str_contains($argv[$i], '=')) {
                        ++$i; // Seulement si ce n'était pas un argument avec =
                    }
                }
                break;
            case '--output-dir':
                if (isset($nextValue)) {
                    $options['output_dir'] = $nextValue;

                    if (! str_contains($argv[$i], '=')) {
                        ++$i; // Seulement si ce n'était pas un argument avec =
                    }
                }
                break;
            case '--force':
                $options['force'] = true;
                break;
            case '--dry-run':
                $options['dry_run'] = true;
                break;
            case '--host':
                if (isset($nextValue)) {
                    $options['host'] = $nextValue;

                    if (! str_contains($argv[$i], '=')) {
                        ++$i;
                    }
                }
                break;
            case '--port':
                if (isset($nextValue)) {
                    $options['port'] = (int) $nextValue;

                    if (! str_contains($argv[$i], '=')) {
                        ++$i;
                    }
                }
                break;
            case '--dbname':
                if (isset($nextValue)) {
                    $options['dbname'] = $nextValue;

                    if (! str_contains($argv[$i], '=')) {
                        ++$i;
                    }
                }
                break;
            case '--user':
                if (isset($nextValue)) {
                    $options['user'] = $nextValue;

                    if (! str_contains($argv[$i], '=')) {
                        ++$i;
                    }
                }
                break;
            case '--password':
                if (isset($nextValue)) {
                    $options['password'] = $nextValue;

                    if (! str_contains($argv[$i], '=')) {
                        ++$i;
                    }
                }
                break;
            case '--help':
                showHelp();
                exit(0);
        }
    }

    return $options;
}

/**
 * Affiche l'aide.
 */
function showHelp(): void
{
    echo "Usage: php generate-entities.php [options]\n\n";
    echo "Options:\n";
    echo "  --namespace <namespace>    Namespace for entities (default: Sakila\\Entity)\n";
    echo "  --output-dir <dir>         Output directory (default: generated/sakila)\n";
    echo "  --force                    Overwrite existing files\n";
    echo "  --dry-run                  Preview without file generation\n";
    echo "  --host <host>              MySQL host (default: mysql)\n";
    echo "  --port <port>              MySQL port (default: 3306)\n";
    echo "  --dbname <dbname>          Database name (default: sakila)\n";
    echo "  --user <user>              MySQL user (default: sakila_user)\n";
    echo "  --password <password>      MySQL password (default: sakila_password)\n";
    echo "  --verbose                  Detailed error display\n";
    echo "  --help                     Show this help\n\n";
    echo "Exemples:\n";
    echo "  php generate-entities.php\n";
    echo "  php generate-entities.php --namespace=\"MyApp\\Entity\" --output-dir=\"src/Entity\"\n";
    echo "  php generate-entities.php --dry-run\n";
}
