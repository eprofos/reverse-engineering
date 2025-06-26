# API Documentation - ReverseEngineeringBundle

This comprehensive documentation describes the complete public API of the ReverseEngineeringBundle, including all services, methods, interfaces, and usage patterns for developers integrating the bundle into their applications.

## 📋 Table of Contents

- [Core Services](#core-services)
- [Exception Hierarchy](#exception-hierarchy)
- [Configuration System](#configuration-system)
- [CLI Commands](#cli-commands)
- [Usage Examples](#usage-examples)
- [Extension Points](#extension-points)

## 🔧 Core Services

### ReverseEngineeringService

**Primary orchestration service** that coordinates the entire entity generation process.

#### Class Definition

```php
namespace App\Service;

use App\Service\DatabaseAnalyzer;
use App\Service\MetadataExtractor;
use App\Service\EntityGenerator;
use App\Service\FileWriter;
use App\Exception\ReverseEngineeringException;

class ReverseEngineeringService
{
    public function __construct(
        private readonly DatabaseAnalyzer $databaseAnalyzer,
        private readonly MetadataExtractor $metadataExtractor,
        private readonly EntityGenerator $entityGenerator,
        private readonly FileWriter $fileWriter,
        private readonly array $config = []
    ) {}
}
```

#### Public Methods

##### `generateEntities(array $options = []): array`

Generates entities from database schema with comprehensive options.

**Parameters:**
- `$options` (array): Generation configuration options
  - `tables` (array): Specific tables to process (empty = all tables)
  - `exclude` (array): Tables to exclude from processing
  - `namespace` (string): Target namespace for generated entities
  - `output_dir` (string): Output directory for generated files
  - `force` (bool): Force overwrite existing files
  - `dry_run` (bool): Preview mode without file creation
  - `generate_repository` (bool): Generate repository classes
  - `use_annotations` (bool): Use annotations instead of PHP 8 attributes

**Return Value:**
```php
[
    'entities' => [
        [
            'class_name' => 'User',
            'namespace' => 'App\\Entity',
            'filename' => 'User.php',
            'content' => '<?php...',
            'table_name' => 'users',
            'relationships' => [...],
            'repository' => [...]
        ],
        // ... additional entities
    ],
    'files' => [
        '/path/to/User.php',
        '/path/to/UserRepository.php',
        // ... additional file paths
    ],
    'tables_processed' => 15,
    'generation_time' => 12.34,
    'memory_usage' => '45MB'
]
```

**Exceptions:**
- `ReverseEngineeringException`: General process errors
- `DatabaseConnectionException`: Database connectivity issues
- `MetadataExtractionException`: Schema analysis failures
- `EntityGenerationException`: Code generation errors
- `FileWriteException`: File system operation failures

**Usage Example:**
```php
$service = $container->get(ReverseEngineeringService::class);

try {
    $result = $service->generateEntities([
        'tables' => ['users', 'products', 'orders'],
        'namespace' => 'App\\Entity\\Shop',
        'output_dir' => 'src/Entity/Shop',
        'force' => true,
        'generate_repository' => true
    ]);
    
    echo "Generated {$result['tables_processed']} entities in {$result['generation_time']}s\n";
    echo "Files created: " . count($result['files']) . "\n";
    echo "Memory used: {$result['memory_usage']}\n";
    
} catch (ReverseEngineeringException $e) {
    echo "Generation failed: " . $e->getMessage() . "\n";
}
```

##### `validateDatabaseConnection(): bool`

Validates database connectivity and accessibility.

**Return Value:** `true` if connection is valid and database is accessible

**Exceptions:**
- `DatabaseConnectionException`: Connection or authentication failures

**Usage Example:**
```php
try {
    $isValid = $service->validateDatabaseConnection();
    if ($isValid) {
        echo "Database connection is valid\n";
    }
} catch (DatabaseConnectionException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
```

##### `getAvailableTables(): array`

Retrieves list of all available tables in the database.

**Return Value:** Array of table names

**Usage Example:**
```php
$tables = $service->getAvailableTables();
echo "Available tables: " . implode(', ', $tables) . "\n";
```

##### `getTableInfo(string $tableName): array`

Retrieves detailed information about a specific table.

**Parameters:**
- `$tableName` (string): Name of the table to analyze

**Return Value:**
```php
[
    'name' => 'users',
    'columns' => [
        [
            'name' => 'id',
            'type' => 'int',
            'nullable' => false,
            'primary' => true,
            'auto_increment' => true,
            'default' => null,
            'length' => null
        ],
        // ... additional columns
    ],
    'foreign_keys' => [
        [
            'column' => 'category_id',
            'referenced_table' => 'categories',
            'referenced_column' => 'id',
            'on_delete' => 'CASCADE',
            'on_update' => 'RESTRICT'
        ],
        // ... additional foreign keys
    ],
    'indexes' => [...],
    'primary_key' => ['id'],
    'table_comment' => 'User accounts table'
]
```

**Usage Example:**
```php
$tableInfo = $service->getTableInfo('users');
echo "Table: {$tableInfo['name']}\n";
echo "Columns: " . count($tableInfo['columns']) . "\n";
echo "Foreign Keys: " . count($tableInfo['foreign_keys']) . "\n";
```

---

### DatabaseAnalyzer

**Database structure analysis service** for schema introspection and metadata extraction.

#### Class Definition

```php
namespace App\Service;

use Doctrine\DBAL\Connection;
use App\Exception\DatabaseConnectionException;

class DatabaseAnalyzer
{
    public function __construct(
        private readonly Connection $connection
    ) {}
}
```

#### Public Methods

##### `analyzeTables(array $include = [], array $exclude = []): array`

Analyzes database tables with filtering capabilities.

**Parameters:**
- `$include` (array): Tables to include (empty = all tables)
- `$exclude` (array): Tables to exclude from analysis

**Return Value:** Array of filtered table names

**Usage Example:**
```php
$analyzer = $container->get(DatabaseAnalyzer::class);

// Analyze all tables except system tables
$tables = $analyzer->analyzeTables([], [
    'information_schema',
    'performance_schema',
    'mysql',
    'sys'
]);

// Analyze only specific tables
$userTables = $analyzer->analyzeTables([
    'users',
    'user_profiles',
    'user_permissions'
]);
```

##### `getTableColumns(string $tableName): array`

Retrieves detailed column information for a table.

**Parameters:**
- `$tableName` (string): Name of the table

**Return Value:**
```php
[
    [
        'name' => 'id',
        'type' => 'int',
        'nullable' => false,
        'default' => null,
        'auto_increment' => true,
        'primary' => true,
        'length' => null,
        'precision' => null,
        'scale' => null,
        'unsigned' => true,
        'comment' => 'Primary key'
    ],
    [
        'name' => 'email',
        'type' => 'varchar',
        'nullable' => false,
        'default' => null,
        'auto_increment' => false,
        'primary' => false,
        'length' => 255,
        'precision' => null,
        'scale' => null,
        'unsigned' => false,
        'comment' => 'User email address'
    ],
    // ... additional columns
]
```

##### `getForeignKeys(string $tableName): array`

Retrieves foreign key constraints for a table.

**Parameters:**
- `$tableName` (string): Name of the table

**Return Value:**
```php
[
    [
        'column' => 'user_id',
        'referenced_table' => 'users',
        'referenced_column' => 'id',
        'constraint_name' => 'fk_posts_user_id',
        'on_delete' => 'CASCADE',
        'on_update' => 'RESTRICT'
    ],
    [
        'column' => 'category_id',
        'referenced_table' => 'categories',
        'referenced_column' => 'id',
        'constraint_name' => 'fk_posts_category_id',
        'on_delete' => 'SET NULL',
        'on_update' => 'CASCADE'
    ],
    // ... additional foreign keys
]
```

##### `getIndexes(string $tableName): array`

Retrieves index information for a table.

**Return Value:**
```php
[
    [
        'name' => 'idx_email',
        'columns' => ['email'],
        'unique' => true,
        'primary' => false
    ],
    [
        'name' => 'idx_created_at',
        'columns' => ['created_at'],
        'unique' => false,
        'primary' => false
    ],
    // ... additional indexes
]
```

##### `testConnection(): bool`

Tests database connectivity.

**Return Value:** `true` if connection is successful

##### `listTables(): array`

Lists all tables in the database.

**Return Value:** Array of all table names

---

### MetadataExtractor

**Metadata transformation service** that converts database schema to entity metadata.

#### Class Definition

```php
namespace App\Service;

use App\Service\DatabaseAnalyzer;
use App\Service\MySQLTypeMapper;

class MetadataExtractor
{
    public function __construct(
        private readonly DatabaseAnalyzer $databaseAnalyzer,
        private readonly MySQLTypeMapper $typeMapper
    ) {}
}
```

#### Public Methods

##### `extractTableMetadata(string $tableName, array $allTables = []): array`

Extracts and transforms table metadata for entity generation.

**Parameters:**
- `$tableName` (string): Name of the table to process
- `$allTables` (array): List of all available tables (for relationship detection)

**Return Value:**
```php
[
    'table_name' => 'users',
    'class_name' => 'User',
    'namespace' => 'App\\Entity',
    'columns' => [
        [
            'name' => 'id',
            'property_name' => 'id',
            'php_type' => 'int',
            'doctrine_type' => 'integer',
            'nullable' => false,
            'primary' => true,
            'generated' => true,
            'default' => null,
            'length' => null,
            'precision' => null,
            'scale' => null,
            'options' => []
        ],
        [
            'name' => 'email',
            'property_name' => 'email',
            'php_type' => 'string',
            'doctrine_type' => 'string',
            'nullable' => false,
            'primary' => false,
            'generated' => false,
            'default' => null,
            'length' => 255,
            'precision' => null,
            'scale' => null,
            'options' => ['unique' => true]
        ],
        // ... additional columns
    ],
    'relations' => [
        [
            'type' => 'ManyToOne',
            'property' => 'category',
            'target_entity' => 'Category',
            'join_column' => 'category_id',
            'referenced_column' => 'id',
            'nullable' => false,
            'on_delete' => 'CASCADE'
        ],
        // ... additional relations
    ],
    'repository' => 'UserRepository',
    'table_comment' => 'User accounts and profiles'
]
```

##### `mapColumnType(string $dbType, string $driver): array`

Maps database column type to PHP and Doctrine types.

**Parameters:**
- `$dbType` (string): Database column type (e.g., 'VARCHAR(255)', 'INT(11)')
- `$driver` (string): Database driver (pdo_mysql, pdo_pgsql, pdo_sqlite)

**Return Value:**
```php
[
    'php_type' => 'string',
    'doctrine_type' => 'string',
    'length' => 255,
    'precision' => null,
    'scale' => null,
    'options' => []
]
```

**Usage Example:**
```php
$extractor = $container->get(MetadataExtractor::class);

// Map MySQL types
$stringType = $extractor->mapColumnType('VARCHAR(255)', 'pdo_mysql');
// Result: ['php_type' => 'string', 'doctrine_type' => 'string', 'length' => 255, ...]

$intType = $extractor->mapColumnType('INT(11)', 'pdo_mysql');
// Result: ['php_type' => 'int', 'doctrine_type' => 'integer', ...]

$decimalType = $extractor->mapColumnType('DECIMAL(10,2)', 'pdo_mysql');
// Result: ['php_type' => 'string', 'doctrine_type' => 'decimal', 'precision' => 10, 'scale' => 2, ...]
```

##### `detectRelations(array $foreignKeys, array $allTables): array`

Detects and configures entity relationships from foreign keys.

**Parameters:**
- `$foreignKeys` (array): Foreign key constraints from database
- `$allTables` (array): List of all available tables

**Return Value:** Array of relationship configurations

##### `normalizeNames(string $name): string`

Normalizes database names to PHP naming conventions.

**Parameters:**
- `$name` (string): Database name (table or column)

**Return Value:** Normalized PHP name

**Usage Example:**
```php
$tableName = $extractor->normalizeNames('user_profiles');
// Result: 'UserProfile'

$propertyName = $extractor->normalizeNames('first_name');
// Result: 'firstName'
```

---

### EntityGenerator

**Code generation service** that creates PHP entity classes from metadata.

#### Class Definition

```php
namespace App\Service;

use Twig\Environment;
use App\Exception\EntityGenerationException;

class EntityGenerator
{
    public function __construct(
        private readonly Environment $twig,
        private readonly array $config = []
    ) {}
}
```

#### Public Methods

##### `generateEntity(string $tableName, array $metadata, array $options = []): array`

Generates PHP entity code from table metadata.

**Parameters:**
- `$tableName` (string): Source table name
- `$metadata` (array): Table metadata from MetadataExtractor
- `$options` (array): Generation options
  - `namespace` (string): Entity namespace
  - `use_annotations` (bool): Use annotations instead of attributes
  - `generate_repository` (bool): Generate repository class
  - `template` (string): Custom template name

**Return Value:**
```php
[
    'class_name' => 'User',
    'namespace' => 'App\\Entity',
    'filename' => 'User.php',
    'content' => '<?php

declare(strict_types=1);

namespace App\\Entity;

use Doctrine\\ORM\\Mapping as ORM;
use DateTimeInterface;

#[ORM\\Entity(repositoryClass: App\\Repository\\UserRepository::class)]
#[ORM\\Table(name: \'users\')]
class User
{
    #[ORM\\Id]
    #[ORM\\GeneratedValue]
    #[ORM\\Column(type: \'integer\')]
    private int $id;
    
    // ... rest of entity code
}',
    'repository' => [
        'class_name' => 'UserRepository',
        'namespace' => 'App\\Repository',
        'filename' => 'UserRepository.php',
        'content' => '<?php...'
    ],
    'relationships' => [
        [
            'type' => 'ManyToOne',
            'property' => 'category',
            'target_entity' => 'Category'
        ]
    ]
]
```

##### `generateRepository(string $entityName, array $options = []): array`

Generates repository class for an entity.

**Parameters:**
- `$entityName` (string): Entity class name
- `$options` (array): Generation options

**Return Value:**
```php
[
    'class_name' => 'UserRepository',
    'namespace' => 'App\\Repository',
    'filename' => 'UserRepository.php',
    'content' => '<?php...'
]
```

##### `renderTemplate(string $template, array $variables): string`

Renders a Twig template with provided variables.

**Parameters:**
- `$template` (string): Template name (e.g., 'entity.php.twig')
- `$variables` (array): Template variables

**Return Value:** Rendered template content

**Usage Example:**
```php
$generator = $container->get(EntityGenerator::class);

$content = $generator->renderTemplate('entity.php.twig', [
    'class_name' => 'User',
    'namespace' => 'App\\Entity',
    'columns' => [...],
    'relations' => [...]
]);
```

---

### FileWriter

**File system service** for secure file writing and management.

#### Class Definition

```php
namespace App\Service;

use App\Exception\FileWriteException;

class FileWriter
{
    public function __construct(
        private readonly string $projectDir
    ) {}
}
```

#### Public Methods

##### `writeEntityFile(array $entity, ?string $outputDir = null, bool $force = false): string`

Writes entity file to disk with conflict management.

**Parameters:**
- `$entity` (array): Entity data from EntityGenerator
- `$outputDir` (string|null): Output directory (null = use config default)
- `$force` (bool): Force overwrite existing files

**Return Value:** Path to created file

**Exceptions:**
- `FileWriteException`: File system errors, permission issues, or conflicts

**Usage Example:**
```php
$fileWriter = $container->get(FileWriter::class);

try {
    $filePath = $fileWriter->writeEntityFile($entity, 'src/Entity', true);
    echo "Entity written to: $filePath\n";
} catch (FileWriteException $e) {
    echo "Write failed: " . $e->getMessage() . "\n";
}
```

##### `writeRepositoryFile(array $repository, ?string $outputDir = null, bool $force = false): string`

Writes repository file to disk.

**Parameters:** Same as `writeEntityFile`

##### `validateOutputDirectory(string $directory): bool`

Validates that a directory is writable.

**Parameters:**
- `$directory` (string): Directory path to validate

**Return Value:** `true` if directory is writable

##### `handleFileConflict(string $filePath, bool $force): bool`

Handles conflicts with existing files.

**Parameters:**
- `$filePath` (string): Path to potentially conflicting file
- `$force` (bool): Whether to force overwrite

**Return Value:** `true` if file can be written

---

## ⚠️ Exception Hierarchy

### Base Exception

#### ReverseEngineeringException

**Base exception class** for all bundle-related errors.

```php
namespace App\Exception;

class ReverseEngineeringException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    public function getContext(): array
    {
        return [
            'bundle' => 'ReverseEngineeringBundle',
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true)
        ];
    }
}
```

### Specialized Exceptions

#### DatabaseConnectionException

**Database connectivity and access errors.**

```php
namespace App\Exception;

class DatabaseConnectionException extends ReverseEngineeringException
{
    public function __construct(
        string $message = '',
        string $driver = '',
        string $host = '',
        ?\Throwable $previous = null
    ) {
        $this->driver = $driver;
        $this->host = $host;
        parent::__construct($message, 1001, $previous);
    }
    
    public function getDriver(): string
    {
        return $this->driver;
    }
    
    public function getHost(): string
    {
        return $this->host;
    }
}
```

**Common Scenarios:**
- Invalid database credentials
- Database server unreachable
- Missing database drivers
- Insufficient database permissions

#### MetadataExtractionException

**Schema analysis and metadata extraction errors.**

```php
namespace App\Exception;

class MetadataExtractionException extends ReverseEngineeringException
{
    public function __construct(
        string $message = '',
        string $tableName = '',
        ?\Throwable $previous = null
    ) {
        $this->tableName = $tableName;
        parent::__construct($message, 1002, $previous);
    }
    
    public function getTableName(): string
    {
        return $this->tableName;
    }
}
```

**Common Scenarios:**
- Table does not exist
- Insufficient table permissions
- Unsupported column types
- Corrupted table structure

#### EntityGenerationException

**Entity code generation errors.**

```php
namespace App\Exception;

class EntityGenerationException extends ReverseEngineeringException
{
    public function __construct(
        string $message = '',
        string $entityName = '',
        ?\Throwable $previous = null
    ) {
        $this->entityName = $entityName;
        parent::__construct($message, 1003, $previous);
    }
    
    public function getEntityName(): string
    {
        return $this->entityName;
    }
}
```

**Common Scenarios:**
- Template rendering errors
- Invalid metadata structure
- Namespace conflicts
- Memory exhaustion during generation

#### FileWriteException

**File system operation errors.**

```php
namespace App\Exception;

class FileWriteException extends ReverseEngineeringException
{
    public function __construct(
        string $message = '',
        string $filePath = '',
        ?\Throwable $previous = null
    ) {
        $this->filePath = $filePath;
        parent::__construct($message, 1004, $previous);
    }
    
    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
```

**Common Scenarios:**
- Insufficient file permissions
- Disk space exhaustion
- Invalid file paths
- File conflicts without force option

---

## ⚙️ Configuration System

### Configuration Structure

```yaml
# config/packages/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        dbname: myapp
        user: username
        password: password
        charset: utf8mb4
        options:
            1002: "SET SESSION sql_mode=''"  # PDO::MYSQL_ATTR_INIT_COMMAND
    
    generation:
        namespace: App\Entity
        output_dir: src/Entity
        generate_repository: true
        use_annotations: false
        tables: []
        exclude_tables:
            - doctrine_migration_versions
            - messenger_messages
        
    templates:
        entity: '@ReverseEngineering/entity.php.twig'
        repository: '@ReverseEngineering/repository.php.twig'
        
    type_mapping:
        # Custom type mappings
        custom_enum: string
        special_decimal: decimal
```

### Configuration Access

```php
// Access configuration in services
class CustomService
{
    public function __construct(
        private readonly array $reverseEngineeringConfig
    ) {}
    
    public function getNamespace(): string
    {
        return $this->reverseEngineeringConfig['generation']['namespace'];
    }
}

// Service definition
services:
    App\Service\CustomService:
        arguments:
            $reverseEngineeringConfig: '%reverse_engineering%'
```

---

## 🖥️ CLI Commands

### reverse:generate Command

**Primary command for entity generation.**

#### Command Definition

```php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ReverseGenerateCommand extends Command
{
    protected static $defaultName = 'reverse:generate';
    protected static $defaultDescription = 'Generate Doctrine entities from database schema';
}
```

#### Command Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--tables` | array | [] | Specific tables to process |
| `--exclude` | array | [] | Tables to exclude |
| `--namespace` | string | config | Entity namespace |
| `--output-dir` | string | config | Output directory |
| `--force` | bool | false | Force overwrite existing files |
| `--dry-run` | bool | false | Preview mode |
| `--verbose` | bool | false | Verbose output |

#### Usage Examples

```bash
# Basic generation
php bin/console reverse:generate

# Specific tables with custom namespace
php bin/console reverse:generate \
    --tables=users \
    --tables=products \
    --namespace="App\Entity\Shop" \
    --output-dir="src/Entity/Shop"

# Dry run with verbose output
php bin/console reverse:generate --dry-run --verbose

# Force overwrite with exclusions
php bin/console reverse:generate \
    --force \
    --exclude=cache_items \
    --exclude=sessions
```

#### Exit Codes

- `0`: Success
- `1`: General error
- `2`: Configuration error
- `3`: Database connection error
- `4`: Generation error
- `5`: File write error

---

## 💡 Usage Examples

### Basic Service Usage

```php
use App\Service\ReverseEngineeringService;
use App\Exception\ReverseEngineeringException;

class EntityGenerationController
{
    public function __construct(
        private ReverseEngineeringService $reverseService
    ) {}
    
    public function generateAction(): Response
    {
        try {
            $result = $this->reverseService->generateEntities([
                'tables' => ['users', 'products'],
                'namespace' => 'App\\Entity\\Generated',
                'output_dir' => 'src/Entity/Generated',
                'force' => true
            ]);
            
            return new JsonResponse([
                'success' => true,
                'entities_generated' => count($result['entities']),
                'files_created' => count($result['files']),
                'generation_time' => $result['generation_time']
            ]);
            
        } catch (ReverseEngineeringException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], 400);
        }
    }
}
```

### Advanced Database Analysis

```php
use App\Service\DatabaseAnalyzer;
use App\Service\MetadataExtractor;

class SchemaAnalysisService
{
    public function __construct(
        private DatabaseAnalyzer $analyzer,
        private MetadataExtractor $extractor
    ) {}
    
    public function analyzeSchema(): array
    {
        $analysis = [];
        $tables = $this->analyzer->listTables();
        
        foreach ($tables as $table) {
            $columns = $this->analyzer->getTableColumns($table);
            $foreignKeys = $this->analyzer->getForeignKeys($table);
            $metadata = $this->extractor->extractTableMetadata($table, $tables);
            
            $analysis[$table] = [
                'column_count' => count($columns),
                'foreign_key_count' => count($foreignKeys),
                'has_primary_key' => !empty($metadata['primary_key']),
                'estimated_complexity' => $this->calculateComplexity($metadata),
                'relationships' => array_map(
                    fn($rel) => $rel['type'],
                    $metadata['relations']
                )
            ];
        }
        
        return $analysis;
    }
    
    private function calculateComplexity(array $metadata): string
    {
        $score = count($metadata['columns']) + (count($metadata['relations']) * 2);
        
        return match (true) {
            $score <= 5 => 'simple',
            $score <= 15 => 'medium',
            default => 'complex'
        };
    }
}
```

### Custom Entity Generation

```php
use App\Service\EntityGenerator;
use App\Service\MetadataExtractor;

class CustomEntityService
{
    public function __construct(
        private EntityGenerator $generator,
        private MetadataExtractor $extractor
    ) {}
    
    public function generateWithCustomTemplate(string $tableName): string
    {
        // Extract metadata
        $metadata = $this->extractor->extractTableMetadata($tableName);
        
        // Add custom variables
        $customMetadata = array_merge($metadata, [
            'author' => 'Custom Generator',
            'version' => '2.0',
            'generated_at' => date('Y-m-d H:i:s'),
            'custom_methods' => $this->getCustomMethods($tableName)
        ]);
        
        // Generate with custom template
        $entity = $this->generator->generateEntity(
            $tableName,
            $customMetadata,
            [
                'template' => 'custom_entity.php.twig',
                'namespace' => 'App\\Entity\\Custom'
            ]
        );
        
        return $entity['content'];
    }
    
    private function getCustomMethods(string $tableName): array
    {
        // Define custom methods based on table name
        $methods = [
            'users' => ['getFullName', 'isActive', 'getDisplayName'],
            'products' => ['getFormattedPrice', 'isInStock', 'getDiscountedPrice'],
            'orders' => ['getTotalAmount', 'isCompleted', 'getStatusLabel']
        ];
        
        return $methods[$tableName] ?? [];
    }
}
```

### Error Handling Patterns

```php
use App\Exception\DatabaseConnectionException;
use App\Exception\MetadataExtractionException;
use App\Exception\EntityGenerationException;
use App\Exception\FileWriteException;

class RobustGenerationService
{
    public function generateWithErrorHandling(array $options): array
    {
        $results = [
            'success' => [],
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            $result = $this->reverseService->generateEntities($options);
            $results['success'] = $result;
            
        } catch (DatabaseConnectionException $e) {
            $results['errors'][] = [
                'type' => 'database_connection',
                'message' => 'Failed to connect to database',
                'details' => $e->getMessage(),
                'driver' => $e->getDriver(),
                'host' => $e->getHost(),
                'suggestions' => [
                    'Check database credentials',
                    'Verify database server is running',
                    'Check network connectivity'
                ]
            ];
            
        } catch (MetadataExtractionException $e) {
            $results['errors'][] = [
                '