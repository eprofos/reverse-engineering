<?php

declare(strict_types=1);

namespace App\Tests\Performance;

use App\Service\DatabaseAnalyzer;
use App\Service\MetadataExtractor;
use App\Service\EntityGenerator;
use App\Service\FileWriter;
use App\Service\ReverseEngineeringService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Tests de performance pour le reverse engineering.
 */
class ReverseEngineeringPerformanceTest extends TestCase
{
    private Connection $connection;
    private ReverseEngineeringService $service;
    private string $tempDir;

    protected function setUp(): void
    {
        // Créer une base de données SQLite en mémoire
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        // Créer un répertoire temporaire
        $this->tempDir = sys_get_temp_dir() . '/perf_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $this->setupServices();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testPerformanceWithManyTables(): void
    {
        // Arrange - Créer 50 tables
        $tableCount = 50;
        $this->createManyTables($tableCount);

        // Act - Mesurer le temps d'exécution
        $startTime = microtime(true);
        
        $result = $this->service->generateEntities([
            'output_dir' => $this->tempDir,
            'dry_run' => true, // Éviter l'écriture de fichiers pour se concentrer sur l'analyse
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert
        $this->assertEquals($tableCount, $result['tables_processed']);
        $this->assertCount($tableCount, $result['entities']);
        
        // Le processus ne doit pas prendre plus de 10 secondes pour 50 tables
        $this->assertLessThan(10.0, $executionTime, 
            "Le traitement de {$tableCount} tables a pris {$executionTime}s, ce qui est trop lent");
        
        // Afficher les métriques de performance
        $this->addToAssertionCount(1); // Pour éviter les warnings sur les tests sans assertions
        echo "\n--- Métriques de performance ---\n";
        echo "Tables traitées: {$tableCount}\n";
        echo "Temps d'exécution: " . round($executionTime, 3) . "s\n";
        echo "Temps par table: " . round($executionTime / $tableCount * 1000, 2) . "ms\n";
    }

    public function testPerformanceWithLargeTables(): void
    {
        // Arrange - Créer des tables avec beaucoup de colonnes
        $this->createLargeTable();

        // Act
        $startTime = microtime(true);
        
        $result = $this->service->generateEntities([
            'tables' => ['large_table'],
            'output_dir' => $this->tempDir,
            'dry_run' => true,
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert
        $this->assertEquals(1, $result['tables_processed']);
        $entity = $result['entities'][0];
        
        // Vérifier qu'on a bien toutes les colonnes
        $this->assertGreaterThan(45, count($entity['properties'])); // 50 colonnes - quelques FK
        
        // Le traitement d'une grande table ne doit pas prendre plus de 2 secondes
        $this->assertLessThan(2.0, $executionTime,
            "Le traitement d'une grande table a pris {$executionTime}s, ce qui est trop lent");

        echo "\n--- Métriques table large ---\n";
        echo "Colonnes traitées: " . count($entity['properties']) . "\n";
        echo "Temps d'exécution: " . round($executionTime, 3) . "s\n";
    }

    public function testPerformanceWithComplexRelations(): void
    {
        // Arrange - Créer des tables avec de nombreuses relations
        $this->createTablesWithComplexRelations();

        // Act
        $startTime = microtime(true);
        
        $result = $this->service->generateEntities([
            'output_dir' => $this->tempDir,
            'dry_run' => true,
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert
        $this->assertEquals(6, $result['tables_processed']); // 6 tables avec relations
        
        // Vérifier que les relations sont détectées
        $totalRelations = 0;
        foreach ($result['entities'] as $entity) {
            $totalRelations += count($entity['relations']);
        }
        $this->assertGreaterThan(5, $totalRelations);
        
        // Le traitement ne doit pas prendre plus de 3 secondes
        $this->assertLessThan(3.0, $executionTime,
            "Le traitement des relations complexes a pris {$executionTime}s, ce qui est trop lent");

        echo "\n--- Métriques relations complexes ---\n";
        echo "Relations détectées: {$totalRelations}\n";
        echo "Temps d'exécution: " . round($executionTime, 3) . "s\n";
    }

    public function testMemoryUsageWithManyEntities(): void
    {
        // Arrange
        $this->createManyTables(30);
        
        // Act - Mesurer l'utilisation mémoire
        $memoryBefore = memory_get_usage(true);
        
        $result = $this->service->generateEntities([
            'output_dir' => $this->tempDir,
            'dry_run' => true,
        ]);
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Assert
        $this->assertEquals(30, $result['tables_processed']);
        
        // L'utilisation mémoire ne doit pas dépasser 50MB
        $maxMemoryMB = 50;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;
        
        $this->assertLessThan($maxMemoryMB, $memoryUsedMB,
            "L'utilisation mémoire ({$memoryUsedMB}MB) dépasse la limite de {$maxMemoryMB}MB");

        echo "\n--- Métriques mémoire ---\n";
        echo "Mémoire utilisée: " . round($memoryUsedMB, 2) . "MB\n";
        echo "Mémoire par table: " . round($memoryUsedMB / 30, 3) . "MB\n";
    }

    public function testFileGenerationPerformance(): void
    {
        // Arrange
        $this->createManyTables(20);

        // Act - Tester la génération de fichiers réels
        $startTime = microtime(true);
        
        $result = $this->service->generateEntities([
            'output_dir' => $this->tempDir,
            'dry_run' => false, // Génération réelle de fichiers
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert
        $this->assertEquals(20, $result['tables_processed']);
        $this->assertCount(40, $result['files']); // 20 entités + 20 repositories
        
        // Vérifier que tous les fichiers existent
        foreach ($result['files'] as $file) {
            $this->assertFileExists($file);
        }
        
        // La génération de 40 fichiers ne doit pas prendre plus de 5 secondes
        $this->assertLessThan(5.0, $executionTime,
            "La génération de fichiers a pris {$executionTime}s, ce qui est trop lent");

        echo "\n--- Métriques génération fichiers ---\n";
        echo "Fichiers générés: " . count($result['files']) . "\n";
        echo "Temps d'exécution: " . round($executionTime, 3) . "s\n";
        echo "Temps par fichier: " . round($executionTime / count($result['files']) * 1000, 2) . "ms\n";
    }

    public function testDatabaseAnalysisPerformance(): void
    {
        // Arrange
        $this->createManyTables(100);

        // Act - Tester uniquement l'analyse de la base de données
        $startTime = microtime(true);
        
        $tables = $this->service->getAvailableTables();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert
        $this->assertCount(100, $tables);
        
        // L'analyse de 100 tables ne doit pas prendre plus de 1 seconde
        $this->assertLessThan(1.0, $executionTime,
            "L'analyse de la base de données a pris {$executionTime}s, ce qui est trop lent");

        echo "\n--- Métriques analyse BDD ---\n";
        echo "Tables analysées: " . count($tables) . "\n";
        echo "Temps d'exécution: " . round($executionTime, 3) . "s\n";
    }

    private function setupServices(): void
    {
        $databaseConfig = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Passer la connexion existante au DatabaseAnalyzer pour partager la même base de données
        $databaseAnalyzer = new DatabaseAnalyzer($databaseConfig, $this->connection);
        $metadataExtractor = new MetadataExtractor($databaseAnalyzer);

        // Template minimal pour les tests de performance
        $loader = new ArrayLoader([
            'entity.php.twig' => '<?php class {{ entity_name }} { /* properties */ }',
        ]);
        $twig = new Environment($loader);

        $entityGenerator = new EntityGenerator($twig);
        $fileWriter = new FileWriter($this->tempDir);

        $this->service = new ReverseEngineeringService(
            $databaseAnalyzer,
            $metadataExtractor,
            $entityGenerator,
            $fileWriter
        );
    }

    private function createManyTables(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $tableName = "table_{$i}";
            $this->connection->executeStatement("
                CREATE TABLE {$tableName} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME,
                    is_active BOOLEAN DEFAULT 1
                )
            ");
        }
    }

    private function createLargeTable(): void
    {
        $columns = ['id INTEGER PRIMARY KEY AUTOINCREMENT'];
        
        // Créer 50 colonnes de différents types
        for ($i = 1; $i <= 50; $i++) {
            $type = match($i % 5) {
                0 => 'TEXT',
                1 => 'INTEGER',
                2 => 'DECIMAL(10,2)',
                3 => 'BOOLEAN',
                4 => 'DATETIME',
            };
            $columns[] = "column_{$i} {$type}";
        }
        
        $sql = "CREATE TABLE large_table (" . implode(', ', $columns) . ")";
        $this->connection->executeStatement($sql);
    }

    private function createTablesWithComplexRelations(): void
    {
        // Table principale
        $this->connection->executeStatement('
            CREATE TABLE companies (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            )
        ');

        $this->connection->executeStatement('
            CREATE TABLE departments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                company_id INTEGER,
                FOREIGN KEY (company_id) REFERENCES companies(id)
            )
        ');

        $this->connection->executeStatement('
            CREATE TABLE employees (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                department_id INTEGER,
                manager_id INTEGER,
                FOREIGN KEY (department_id) REFERENCES departments(id),
                FOREIGN KEY (manager_id) REFERENCES employees(id)
            )
        ');

        $this->connection->executeStatement('
            CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                company_id INTEGER,
                FOREIGN KEY (company_id) REFERENCES companies(id)
            )
        ');

        $this->connection->executeStatement('
            CREATE TABLE employee_projects (
                employee_id INTEGER,
                project_id INTEGER,
                role TEXT,
                PRIMARY KEY (employee_id, project_id),
                FOREIGN KEY (employee_id) REFERENCES employees(id),
                FOREIGN KEY (project_id) REFERENCES projects(id)
            )
        ');

        $this->connection->executeStatement('
            CREATE TABLE tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                project_id INTEGER,
                assigned_to INTEGER,
                FOREIGN KEY (project_id) REFERENCES projects(id),
                FOREIGN KEY (assigned_to) REFERENCES employees(id)
            )
        ');
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}