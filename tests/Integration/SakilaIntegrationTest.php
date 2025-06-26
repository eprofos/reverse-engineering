<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\DatabaseAnalyzer;
use App\Service\MetadataExtractor;
use App\Service\EntityGenerator;
use App\Service\FileWriter;
use App\Service\ReverseEngineeringService;
use App\Tests\TestHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Tests d'intégration avec la base de données Sakila.
 * 
 * Ces tests valident le processus de rétro-ingénierie sur une base de données
 * complexe et réaliste avec de nombreuses relations et types de données variés.
 */
class SakilaIntegrationTest extends TestCase
{
    private Connection $connection;
    private ReverseEngineeringService $service;
    private string $tempDir;
    
    /**
     * Tables principales de Sakila à tester.
     */
    private const MAIN_TABLES = [
        'actor',
        'address', 
        'category',
        'city',
        'country',
        'customer',
        'film',
        'film_actor',
        'film_category',
        'inventory',
        'language',
        'payment',
        'rental',
        'staff',
        'store'
    ];

    /**
     * Relations attendues dans Sakila.
     */
    private const EXPECTED_RELATIONS = [
        'address' => ['city'],
        'city' => ['country'],
        'customer' => ['address', 'store'],
        'film' => ['language'],
        'film_actor' => ['actor', 'film'],
        'film_category' => ['film', 'category'],
        'inventory' => ['film', 'store'],
        'payment' => ['customer', 'staff', 'rental'],
        'rental' => ['inventory', 'customer', 'staff'],
        'staff' => ['address', 'store'],
        'store' => ['staff', 'address']
    ];

    protected function setUp(): void
    {
        // Configuration de la connexion Docker MySQL
        $this->connection = $this->createDockerConnection();
        
        // Créer un répertoire temporaire pour les fichiers générés
        $this->tempDir = TestHelper::createTempDirectory('sakila_test_');
        
        // Configurer les services
        $this->setupServices();
    }

    protected function tearDown(): void
    {
        // Nettoyer le répertoire temporaire
        TestHelper::removeDirectory($this->tempDir);
    }

    /**
     * Test de connexion à la base de données Sakila.
     */
    public function testSakilaDatabaseConnection(): void
    {
        $this->assertTrue($this->service->validateDatabaseConnection());
        
        // Vérifier que nous sommes bien connectés à Sakila
        $tables = $this->service->getAvailableTables();
        $this->assertGreaterThanOrEqual(15, count($tables));
        
        foreach (self::MAIN_TABLES as $table) {
            $this->assertContains($table, $tables, "Table {$table} manquante dans Sakila");
        }
    }

    /**
     * Test de génération complète des entités Sakila.
     */
    public function testCompleteEntityGeneration(): void
    {
        $result = $this->service->generateEntities([
            'output_dir' => $this->tempDir,
            'namespace' => 'Sakila\\Entity',
            'tables' => self::MAIN_TABLES,
        ]);

        // Vérifications générales
        $this->assertIsArray($result);
        $this->assertArrayHasKey('entities', $result);
        $this->assertArrayHasKey('files', $result);
        $this->assertArrayHasKey('tables_processed', $result);

        // Vérifier le nombre d'entités générées
        $this->assertEquals(count(self::MAIN_TABLES), $result['tables_processed']);
        $this->assertCount(count(self::MAIN_TABLES), $result['entities']);

        // Vérifier que tous les fichiers ont été créés
        $expectedFiles = count(self::MAIN_TABLES) * 2; // entités + repositories
        $this->assertCount($expectedFiles, $result['files']);

        // Vérifier la structure des entités générées
        $this->verifyGeneratedEntities($result['entities']);
    }

    /**
     * Test des relations complexes dans Sakila.
     */
    public function testComplexRelations(): void
    {
        $result = $this->service->generateEntities([
            'output_dir' => $this->tempDir,
            'tables' => array_keys(self::EXPECTED_RELATIONS),
        ]);

        foreach ($result['entities'] as $entity) {
            $tableName = $entity['table'];
            
            if (isset(self::EXPECTED_RELATIONS[$tableName])) {
                $this->assertArrayHasKey('relations', $entity);
                $this->assertNotEmpty($entity['relations'], 
                    "Aucune relation trouvée pour la table {$tableName}");
                
                // Vérifier que les relations attendues sont présentes
                $relationTargets = array_column($entity['relations'], 'target_entity');
                
                foreach (self::EXPECTED_RELATIONS[$tableName] as $expectedTarget) {
                    $expectedEntityName = $this->tableToEntityName($expectedTarget);
                    $this->assertContains($expectedEntityName, $relationTargets,
                        "Relation manquante: {$tableName} -> {$expectedTarget}");
                }
            }
        }
    }

    /**
     * Test des types de données variés dans Sakila.
     */
    public function testDataTypeMapping(): void
    {
        // Tester spécifiquement la table film qui a de nombreux types
        $result = $this->service->generateEntities([
            'tables' => ['film'],
            'output_dir' => $this->tempDir,
        ]);

        $filmEntity = $result['entities'][0];
        $this->assertEquals('Film', $filmEntity['name']);

        $propertyTypes = [];
        foreach ($filmEntity['properties'] as $property) {
            $propertyTypes[$property['name']] = $property['type'];
        }

        // Vérifications des types de données
        $this->assertEquals('int', $propertyTypes['filmId']);
        $this->assertEquals('string', $propertyTypes['title']);
        $this->assertEquals('?string', $propertyTypes['description']); // TEXT nullable
        $this->assertEquals('?int', $propertyTypes['releaseYear']); // YEAR nullable
        $this->assertEquals('int', $propertyTypes['rentalDuration']); // TINYINT
        $this->assertEquals('string', $propertyTypes['rentalRate']); // DECIMAL
        $this->assertEquals('?int', $propertyTypes['length']); // SMALLINT nullable
        $this->assertEquals('string', $propertyTypes['replacementCost']); // DECIMAL
        $this->assertEquals('\DateTimeInterface', $propertyTypes['lastUpdate']); // TIMESTAMP
    }

    /**
     * Test des contraintes et index dans Sakila.
     */
    public function testConstraintsAndIndexes(): void
    {
        // Tester la table customer qui a plusieurs contraintes
        $tableInfo = $this->service->getTableInfo('customer');

        $this->assertArrayHasKey('primary_key', $tableInfo);
        $this->assertEquals(['customer_id'], $tableInfo['primary_key']);

        // Vérifier les clés étrangères
        $foreignKeys = array_filter($tableInfo['columns'], 
            fn($col) => $col['is_foreign_key'] ?? false);
        
        $this->assertNotEmpty($foreignKeys);
        
        // Vérifier les colonnes de clés étrangères attendues
        $fkColumns = array_column($foreignKeys, 'name');
        $this->assertContains('store_id', $fkColumns);
        $this->assertContains('address_id', $fkColumns);
    }

    /**
     * Test de performance sur la base Sakila complète.
     */
    public function testPerformanceOnFullDatabase(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $result = $this->service->generateEntities([
            'output_dir' => $this->tempDir,
            'namespace' => 'Sakila\\Entity',
        ]);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        // Assertions de performance
        $this->assertLessThan(30.0, $executionTime, 
            "Génération trop lente: {$executionTime}s");
        $this->assertLessThan(128 * 1024 * 1024, $memoryUsed, 
            "Consommation mémoire excessive: " . ($memoryUsed / 1024 / 1024) . "MB");

        // Vérifier que toutes les tables ont été traitées
        $this->assertGreaterThanOrEqual(15, $result['tables_processed']);
    }

    /**
     * Test des entités avec relations Many-to-Many.
     */
    public function testManyToManyRelations(): void
    {
        // Tester les tables de liaison film_actor et film_category
        $result = $this->service->generateEntities([
            'tables' => ['film_actor', 'film_category'],
            'output_dir' => $this->tempDir,
        ]);

        foreach ($result['entities'] as $entity) {
            if (in_array($entity['table'], ['film_actor', 'film_category'])) {
                // Obtenir les métadonnées de la table pour vérifier la clé primaire
                $tableInfo = $this->service->getTableInfo($entity['table']);
                $this->assertArrayHasKey('primary_key', $tableInfo);
                $this->assertCount(2, $tableInfo['primary_key'], 
                    "Clé primaire composite attendue pour {$entity['table']}");
                
                // Vérifier les relations
                $this->assertArrayHasKey('relations', $entity);
                $this->assertCount(2, $entity['relations'], 
                    "Deux relations attendues pour {$entity['table']}");
            }
        }
    }

    /**
     * Test de génération avec exclusion de tables.
     */
    public function testGenerationWithExcludedTables(): void
    {
        $result = $this->service->generateEntities([
            'exclude' => ['film_text', 'staff_list', 'customer_list'],
            'output_dir' => $this->tempDir,
        ]);

        $generatedTables = array_column($result['entities'], 'table');
        
        $this->assertNotContains('film_text', $generatedTables);
        $this->assertContains('film', $generatedTables);
        $this->assertContains('customer', $generatedTables);
    }

    /**
     * Test de validation du code PHP généré.
     */
    public function testGeneratedCodeValidation(): void
    {
        $result = $this->service->generateEntities([
            'tables' => ['actor', 'film', 'customer'],
            'output_dir' => $this->tempDir,
            'namespace' => 'Sakila\\Entity',
        ]);

        foreach ($result['files'] as $filePath) {
            $this->assertFileExists($filePath);
            
            $content = file_get_contents($filePath);
            $this->assertNotEmpty($content);
            
            // Vérifier la syntaxe PHP
            $this->assertTrue(TestHelper::isValidPhpSyntax($content), 
                "Syntaxe PHP invalide dans {$filePath}");
            
            // Vérifications de base du contenu
            $this->assertStringContainsString('<?php', $content);
            $this->assertStringContainsString('declare(strict_types=1);', $content);
            
            // Vérifier le namespace approprié selon le type de fichier
            if (strpos($filePath, 'Repository.php') !== false) {
                $this->assertStringContainsString('namespace Sakila\\Repository', $content);
            } else {
                $this->assertStringContainsString('namespace Sakila\\Entity', $content);
            }
        }
    }

    /**
     * Test des métadonnées extraites pour une table complexe.
     */
    public function testComplexTableMetadata(): void
    {
        $metadata = $this->service->getTableInfo('rental');
        
        $this->assertEquals('Rental', $metadata['entity_name']);
        $this->assertEquals('rental', $metadata['table_name']);
        
        // Vérifier les colonnes
        $columnNames = array_column($metadata['columns'], 'name');
        $expectedColumns = ['rental_id', 'rental_date', 'inventory_id', 
                           'customer_id', 'return_date', 'staff_id', 'last_update'];
        
        foreach ($expectedColumns as $expectedColumn) {
            $this->assertContains($expectedColumn, $columnNames);
        }
        
        // Vérifier les relations
        $this->assertArrayHasKey('relations', $metadata);
        $this->assertCount(3, $metadata['relations']); // inventory, customer, staff
    }

    /**
     * Crée une connexion à la base de données Docker.
     */
    private function createDockerConnection(): Connection
    {
        // Vérifier si MySQL est disponible via le réseau Docker
        if (!$this->isMysqlAvailable()) {
            $this->markTestSkipped('MySQL non disponible via le réseau Docker');
        }

        try {
            return DriverManager::getConnection([
                'driver' => 'pdo_mysql',
                'host' => 'mysql', // Utiliser le hostname Docker
                'port' => 3306,
                'dbname' => 'sakila',
                'user' => 'sakila_user',
                'password' => 'sakila_password',
                'charset' => 'utf8mb4',
            ]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Impossible de se connecter à MySQL Docker: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si MySQL est disponible via le réseau Docker.
     */
    private function isMysqlAvailable(): bool
    {
        try {
            $connection = DriverManager::getConnection([
                'driver' => 'pdo_mysql',
                'host' => 'mysql',
                'port' => 3306,
                'dbname' => 'sakila',
                'user' => 'sakila_user',
                'password' => 'sakila_password',
                'charset' => 'utf8mb4',
            ]);
            $connection->connect();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Configure les services pour les tests.
     */
    private function setupServices(): void
    {
        $databaseConfig = [
            'driver' => 'pdo_mysql',
            'host' => 'mysql', // Utiliser le hostname Docker
            'port' => 3306,
            'dbname' => 'sakila',
            'user' => 'sakila_user',
            'password' => 'sakila_password',
            'charset' => 'utf8mb4',
        ];

        $databaseAnalyzer = new DatabaseAnalyzer($databaseConfig, $this->connection);
        $metadataExtractor = new MetadataExtractor($databaseAnalyzer);

        // Configurer Twig avec les templates
        $loader = new ArrayLoader([
            'entity.php.twig' => $this->getEntityTemplate(),
            'repository.php.twig' => $this->getRepositoryTemplate(),
        ]);
        $twig = new Environment($loader);

        $entityGenerator = new EntityGenerator($twig);
        $fileWriter = new FileWriter('');

        $this->service = new ReverseEngineeringService(
            $databaseAnalyzer,
            $metadataExtractor,
            $entityGenerator,
            $fileWriter
        );
    }

    /**
     * Vérifie la structure des entités générées.
     */
    private function verifyGeneratedEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            // Vérifications de base
            $this->assertArrayHasKey('name', $entity);
            $this->assertArrayHasKey('table', $entity);
            $this->assertArrayHasKey('namespace', $entity);
            $this->assertArrayHasKey('properties', $entity);
            $this->assertArrayHasKey('relations', $entity);

            // Vérifier que le nom d'entité est correct
            $expectedName = $this->tableToEntityName($entity['table']);
            $this->assertEquals($expectedName, $entity['name']);

            // Vérifier les propriétés
            $this->assertIsArray($entity['properties']);
            $this->assertNotEmpty($entity['properties']);

            // Vérifier que chaque propriété a les champs requis
            foreach ($entity['properties'] as $property) {
                $this->assertArrayHasKey('name', $property);
                $this->assertArrayHasKey('type', $property);
                $this->assertArrayHasKey('nullable', $property);
            }
        }
    }

    /**
     * Convertit un nom de table en nom d'entité.
     */
    private function tableToEntityName(string $tableName): string
    {
        return str_replace('_', '', ucwords($tableName, '_'));
    }

    /**
     * Retourne le template Twig pour les entités.
     */
    private function getEntityTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace {{ namespace }};

{% for import in imports %}
use {{ import }};
{% endfor %}

/**
 * Entity {{ entity_name }}.
 * Table: {{ table_name }}
 */
class {{ entity_name }}
{
{% for property in properties %}
    private {{ property.type }} ${{ property.name }};

{% endfor %}
{% for property in properties %}
    public function {{ property.getter_name }}(): {{ property.type }}
    {
        return $this->{{ property.name }};
    }

    public function {{ property.setter_name }}({{ property.type }} ${{ property.name }}): self
    {
        $this->{{ property.name }} = ${{ property.name }};
        return $this;
    }

{% endfor %}
}
';
    }

    /**
     * Retourne le template Twig pour les repositories.
     */
    private function getRepositoryTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace {{ namespace }};

use {{ entity_namespace }}\{{ entity_name }};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l\'entité {{ entity_name }}.
 */
class {{ repository_name }} extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, {{ entity_name }}::class);
    }
}
';
    }
}