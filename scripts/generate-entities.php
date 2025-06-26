#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\DatabaseAnalyzer;
use App\Service\MetadataExtractor;
use App\Service\EntityGenerator;
use App\Service\FileWriter;
use App\Service\ReverseEngineeringService;
use Doctrine\DBAL\DriverManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Script de génération d'entités pour l'environnement Docker.
 * Utilise directement les services PHP comme dans les tests d'intégration.
 */

// Configuration par défaut
$defaultConfig = [
    'host' => 'mysql',
    'port' => 3306,
    'dbname' => 'sakila',
    'user' => 'sakila_user',
    'password' => 'sakila_password',
    'charset' => 'utf8mb4',
    'namespace' => 'Sakila\\Entity',
    'output_dir' => 'generated/sakila',
    'force' => false,
    'dry_run' => false,
];

// Parsing des arguments
$options = parseArguments($argv, $defaultConfig);

try {
    echo "🚀 Génération d'entités depuis Sakila...\n";
    echo "📊 Configuration:\n";
    echo "   - Base de données: {$options['host']}:{$options['port']}/{$options['dbname']}\n";
    echo "   - Namespace: {$options['namespace']}\n";
    echo "   - Répertoire de sortie: {$options['output_dir']}\n";
    echo "   - Mode dry-run: " . ($options['dry_run'] ? 'Oui' : 'Non') . "\n";
    echo "   - Force: " . ($options['force'] ? 'Oui' : 'Non') . "\n\n";

    // Créer le répertoire de sortie
    if (!$options['dry_run'] && !is_dir($options['output_dir'])) {
        mkdir($options['output_dir'], 0755, true);
        echo "📁 Répertoire créé: {$options['output_dir']}\n";
    }

    // Configuration de la base de données
    $databaseConfig = [
        'driver' => 'pdo_mysql',
        'host' => $options['host'],
        'port' => $options['port'],
        'dbname' => $options['dbname'],
        'user' => $options['user'],
        'password' => $options['password'],
        'charset' => $options['charset'],
    ];

    // Créer la connexion
    echo "🔌 Connexion à la base de données...\n";
    $connection = DriverManager::getConnection($databaseConfig);
    $connection->connect();
    echo "✅ Connexion établie avec succès\n\n";

    // Configurer les services
    $databaseAnalyzer = new DatabaseAnalyzer($databaseConfig, $connection);
    $metadataExtractor = new MetadataExtractor($databaseAnalyzer);

    // Configurer Twig
    $loader = new FilesystemLoader(__DIR__ . '/../src/Resources/templates');
    $twig = new Environment($loader);

    $entityGenerator = new EntityGenerator($twig);
    $fileWriter = new FileWriter(__DIR__ . '/..');

    $service = new ReverseEngineeringService(
        $databaseAnalyzer,
        $metadataExtractor,
        $entityGenerator,
        $fileWriter
    );

    // Valider la connexion
    if (!$service->validateDatabaseConnection()) {
        throw new \Exception('Impossible de valider la connexion à la base de données');
    }

    // Obtenir les tables disponibles
    $availableTables = $service->getAvailableTables();
    echo "📋 Tables disponibles: " . count($availableTables) . "\n";
    foreach ($availableTables as $table) {
        echo "   - {$table}\n";
    }
    echo "\n";

    // Générer les entités
    echo "⚙️  Génération des entités...\n";
    $startTime = microtime(true);
    
    $result = $service->generateEntities([
        'output_dir' => $options['output_dir'],
        'namespace' => $options['namespace'],
        'force' => $options['force'],
        'dry_run' => $options['dry_run'],
    ]);

    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 3);

    // Afficher les résultats
    echo "\n🎉 Génération terminée avec succès !\n";
    echo "📊 Statistiques:\n";
    echo "   - Tables traitées: {$result['tables_processed']}\n";
    echo "   - Entités générées: " . count($result['entities']) . "\n";
    echo "   - Fichiers créés: " . count($result['files']) . "\n";
    echo "   - Temps d'exécution: {$executionTime}s\n\n";

    if ($options['dry_run']) {
        echo "🔍 Mode dry-run - Aperçu des entités qui seraient générées:\n";
        foreach ($result['entities'] as $entity) {
            echo "   - {$entity['name']} (table: {$entity['table']}, namespace: {$entity['namespace']})\n";
        }
    } else {
        echo "📁 Fichiers générés:\n";
        foreach ($result['files'] as $file) {
            echo "   - {$file}\n";
        }
    }

    echo "\n✅ Génération d'entités terminée avec succès !\n";

} catch (\Exception $e) {
    echo "\n❌ Erreur lors de la génération: {$e->getMessage()}\n";
    echo "📍 Fichier: {$e->getFile()}:{$e->getLine()}\n";
    
    if (isset($argv) && in_array('--verbose', $argv)) {
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
    
    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        
        switch ($arg) {
            case '--namespace':
                $options['namespace'] = $argv[++$i] ?? $defaultConfig['namespace'];
                break;
            case '--output-dir':
                $options['output_dir'] = $argv[++$i] ?? $defaultConfig['output_dir'];
                break;
            case '--force':
                $options['force'] = true;
                break;
            case '--dry-run':
                $options['dry_run'] = true;
                break;
            case '--host':
                $options['host'] = $argv[++$i] ?? $defaultConfig['host'];
                break;
            case '--port':
                $options['port'] = (int)($argv[++$i] ?? $defaultConfig['port']);
                break;
            case '--dbname':
                $options['dbname'] = $argv[++$i] ?? $defaultConfig['dbname'];
                break;
            case '--user':
                $options['user'] = $argv[++$i] ?? $defaultConfig['user'];
                break;
            case '--password':
                $options['password'] = $argv[++$i] ?? $defaultConfig['password'];
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
    echo "  --namespace <namespace>    Namespace pour les entités (défaut: Sakila\\Entity)\n";
    echo "  --output-dir <dir>         Répertoire de sortie (défaut: generated/sakila)\n";
    echo "  --force                    Écraser les fichiers existants\n";
    echo "  --dry-run                  Aperçu sans génération de fichiers\n";
    echo "  --host <host>              Hôte MySQL (défaut: mysql)\n";
    echo "  --port <port>              Port MySQL (défaut: 3306)\n";
    echo "  --dbname <dbname>          Nom de la base de données (défaut: sakila)\n";
    echo "  --user <user>              Utilisateur MySQL (défaut: sakila_user)\n";
    echo "  --password <password>      Mot de passe MySQL (défaut: sakila_password)\n";
    echo "  --verbose                  Affichage détaillé des erreurs\n";
    echo "  --help                     Afficher cette aide\n\n";
    echo "Exemples:\n";
    echo "  php generate-entities.php\n";
    echo "  php generate-entities.php --namespace=\"MyApp\\Entity\" --output-dir=\"src/Entity\"\n";
    echo "  php generate-entities.php --dry-run\n";
}