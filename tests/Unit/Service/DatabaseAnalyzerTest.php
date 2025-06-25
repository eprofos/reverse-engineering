<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\DatabaseAnalyzer;
use App\Exception\DatabaseConnectionException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\SchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests unitaires pour DatabaseAnalyzer.
 */
class DatabaseAnalyzerTest extends TestCase
{
    private DatabaseAnalyzer $databaseAnalyzer;
    private array $databaseConfig;

    protected function setUp(): void
    {
        $this->databaseConfig = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        
        $this->databaseAnalyzer = new DatabaseAnalyzer($this->databaseConfig);
    }

    public function testTestConnectionSuccess(): void
    {
        // Test avec une vraie connexion SQLite en mémoire
        $result = $this->databaseAnalyzer->testConnection();
        
        $this->assertTrue($result);
    }

    public function testTestConnectionFailure(): void
    {
        // Configuration invalide pour forcer une erreur
        $invalidConfig = [
            'driver' => 'pdo_mysql',
            'host' => 'invalid_host',
            'dbname' => 'invalid_db',
            'user' => 'invalid_user',
            'password' => 'invalid_password',
        ];
        
        $analyzer = new DatabaseAnalyzer($invalidConfig);
        
        $this->expectException(DatabaseConnectionException::class);
        $this->expectExceptionMessage('Impossible de se connecter à la base de données');
        
        $analyzer->testConnection();
    }

    public function testListTablesWithRealDatabase(): void
    {
        // Créer une table de test
        $connection = DriverManager::getConnection($this->databaseConfig);
        $connection->executeStatement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $connection->executeStatement('CREATE TABLE test_posts (id INTEGER PRIMARY KEY, title TEXT)');
        
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        $tables = $analyzer->listTables();
        
        $this->assertIsArray($tables);
        $this->assertContains('test_users', $tables);
        $this->assertContains('test_posts', $tables);
        
        // Nettoyer
        $connection->executeStatement('DROP TABLE test_users');
        $connection->executeStatement('DROP TABLE test_posts');
    }

    public function testListTablesFiltersSystemTables(): void
    {
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        $tables = $analyzer->listTables();
        
        // Vérifier qu'aucune table système n'est retournée
        foreach ($tables as $table) {
            $this->assertFalse(str_starts_with($table, 'sqlite_'));
            $this->assertFalse(str_starts_with($table, 'information_schema'));
            $this->assertFalse(str_starts_with($table, 'performance_schema'));
        }
    }

    public function testAnalyzeTablesWithIncludeFilter(): void
    {
        // Créer des tables de test
        $connection = DriverManager::getConnection($this->databaseConfig);
        $connection->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('CREATE TABLE posts (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('CREATE TABLE comments (id INTEGER PRIMARY KEY)');
        
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        
        // Tester avec filtre d'inclusion
        $result = $analyzer->analyzeTables(['users', 'posts']);
        
        $this->assertCount(2, $result);
        $this->assertContains('users', $result);
        $this->assertContains('posts', $result);
        $this->assertNotContains('comments', $result);
        
        // Nettoyer
        $connection->executeStatement('DROP TABLE users');
        $connection->executeStatement('DROP TABLE posts');
        $connection->executeStatement('DROP TABLE comments');
    }

    public function testAnalyzeTablesWithExcludeFilter(): void
    {
        // Créer des tables de test
        $connection = DriverManager::getConnection($this->databaseConfig);
        $connection->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('CREATE TABLE posts (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('CREATE TABLE temp_table (id INTEGER PRIMARY KEY)');
        
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        
        // Tester avec filtre d'exclusion
        $result = $analyzer->analyzeTables([], ['temp_table']);
        
        $this->assertContains('users', $result);
        $this->assertContains('posts', $result);
        $this->assertNotContains('temp_table', $result);
        
        // Nettoyer
        $connection->executeStatement('DROP TABLE users');
        $connection->executeStatement('DROP TABLE posts');
        $connection->executeStatement('DROP TABLE temp_table');
    }

    public function testGetTableDetailsWithComplexTable(): void
    {
        // Créer une table complexe avec différents types de colonnes
        $connection = DriverManager::getConnection($this->databaseConfig);
        $sql = "
            CREATE TABLE complex_table (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE,
                age INTEGER,
                salary DECIMAL(10,2),
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                description TEXT
            )
        ";
        $connection->executeStatement($sql);
        
        // Créer un index
        $connection->executeStatement('CREATE INDEX idx_email ON complex_table(email)');
        
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        $details = $analyzer->getTableDetails('complex_table');
        
        // Vérifier la structure de base
        $this->assertEquals('complex_table', $details['name']);
        $this->assertIsArray($details['columns']);
        $this->assertIsArray($details['indexes']);
        $this->assertIsArray($details['foreign_keys']);
        $this->assertIsArray($details['primary_key']);
        
        // Vérifier les colonnes
        $this->assertGreaterThan(0, count($details['columns']));
        
        // Vérifier qu'on a bien la clé primaire
        $this->assertContains('id', $details['primary_key']);
        
        // Vérifier les index
        $this->assertGreaterThan(0, count($details['indexes']));
        
        // Nettoyer
        $connection->executeStatement('DROP TABLE complex_table');
    }

    public function testGetTableDetailsWithForeignKeys(): void
    {
        // Créer des tables avec clés étrangères
        $connection = DriverManager::getConnection($this->databaseConfig);
        
        $connection->executeStatement('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )
        ');
        
        $connection->executeStatement('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY,
                title TEXT NOT NULL,
                user_id INTEGER,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');
        
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        $details = $analyzer->getTableDetails('posts');
        
        // Vérifier les clés étrangères
        $this->assertIsArray($details['foreign_keys']);
        
        if (!empty($details['foreign_keys'])) {
            $fk = $details['foreign_keys'][0];
            $this->assertArrayHasKey('name', $fk);
            $this->assertArrayHasKey('local_columns', $fk);
            $this->assertArrayHasKey('foreign_table', $fk);
            $this->assertArrayHasKey('foreign_columns', $fk);
            $this->assertEquals('users', $fk['foreign_table']);
            $this->assertContains('user_id', $fk['local_columns']);
        }
        
        // Nettoyer
        $connection->executeStatement('DROP TABLE posts');
        $connection->executeStatement('DROP TABLE users');
    }

    public function testGetTableDetailsThrowsExceptionForInvalidTable(): void
    {
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        
        $this->expectException(DatabaseConnectionException::class);
        $this->expectExceptionMessage("Erreur lors de l'analyse de la table 'non_existent_table'");
        
        $analyzer->getTableDetails('non_existent_table');
    }

    public function testIsUserTableFiltersSystemTables(): void
    {
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        
        // Utiliser la réflexion pour tester la méthode privée
        $reflection = new \ReflectionClass($analyzer);
        $method = $reflection->getMethod('isUserTable');
        $method->setAccessible(true);
        
        // Tables utilisateur
        $this->assertTrue($method->invoke($analyzer, 'users'));
        $this->assertTrue($method->invoke($analyzer, 'posts'));
        $this->assertTrue($method->invoke($analyzer, 'my_custom_table'));
        
        // Tables système MySQL
        $this->assertFalse($method->invoke($analyzer, 'information_schema_tables'));
        $this->assertFalse($method->invoke($analyzer, 'performance_schema_events'));
        $this->assertFalse($method->invoke($analyzer, 'mysql_user'));
        $this->assertFalse($method->invoke($analyzer, 'sys_config'));
        
        // Tables système PostgreSQL
        $this->assertFalse($method->invoke($analyzer, 'pg_catalog_tables'));
        
        // Tables système SQLite
        $this->assertFalse($method->invoke($analyzer, 'sqlite_master'));
        $this->assertFalse($method->invoke($analyzer, 'sqlite_sequence'));
    }

    public function testGetColumnsInfoExtractsCorrectInformation(): void
    {
        // Créer une table avec différents types de colonnes
        $connection = DriverManager::getConnection($this->databaseConfig);
        $connection->executeStatement('
            CREATE TABLE test_columns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE,
                age INTEGER DEFAULT 0,
                description TEXT
            )
        ');
        
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        $details = $analyzer->getTableDetails('test_columns');
        
        $columns = $details['columns'];
        $this->assertIsArray($columns);
        $this->assertGreaterThan(0, count($columns));
        
        // Vérifier la structure des colonnes
        foreach ($columns as $column) {
            $this->assertArrayHasKey('name', $column);
            $this->assertArrayHasKey('type', $column);
            $this->assertArrayHasKey('nullable', $column);
            $this->assertArrayHasKey('default', $column);
            $this->assertArrayHasKey('auto_increment', $column);
            $this->assertArrayHasKey('comment', $column);
        }
        
        // Nettoyer
        $connection->executeStatement('DROP TABLE test_columns');
    }

    public function testListTablesThrowsExceptionOnConnectionError(): void
    {
        // Configuration invalide
        $invalidConfig = [
            'driver' => 'pdo_mysql',
            'host' => 'invalid_host',
            'dbname' => 'invalid_db',
        ];
        
        $analyzer = new DatabaseAnalyzer($invalidConfig);
        
        $this->expectException(DatabaseConnectionException::class);
        $this->expectExceptionMessage('Erreur lors de la récupération des tables');
        
        $analyzer->listTables();
    }

    public function testAnalyzeTablesReturnsEmptyArrayWhenNoTablesMatch(): void
    {
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        
        // Demander des tables qui n'existent pas
        $result = $analyzer->analyzeTables(['non_existent_table']);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetTableDetailsWithIndexes(): void
    {
        // Créer une table avec plusieurs index
        $connection = DriverManager::getConnection($this->databaseConfig);
        $connection->executeStatement('
            CREATE TABLE indexed_table (
                id INTEGER PRIMARY KEY,
                email TEXT UNIQUE,
                name TEXT,
                category_id INTEGER
            )
        ');
        
        $connection->executeStatement('CREATE INDEX idx_name ON indexed_table(name)');
        $connection->executeStatement('CREATE INDEX idx_category ON indexed_table(category_id)');
        
        $analyzer = new DatabaseAnalyzer($this->databaseConfig);
        $details = $analyzer->getTableDetails('indexed_table');
        
        $indexes = $details['indexes'];
        $this->assertIsArray($indexes);
        $this->assertGreaterThan(0, count($indexes));
        
        // Vérifier la structure des index
        foreach ($indexes as $index) {
            $this->assertArrayHasKey('name', $index);
            $this->assertArrayHasKey('columns', $index);
            $this->assertArrayHasKey('unique', $index);
            $this->assertArrayHasKey('primary', $index);
        }
        
        // Nettoyer
        $connection->executeStatement('DROP TABLE indexed_table');
    }
}