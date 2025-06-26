<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\DatabaseConnectionException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use App\Service\MySQLTypeMapper;

/**
 * Service pour l'analyse de la structure de la base de données.
 */
class DatabaseAnalyzer
{
    private ?Connection $connection = null;

    public function __construct(
        private readonly array $databaseConfig,
        ?Connection $connection = null
    ) {
        $this->connection = $connection;
    }

    /**
     * Teste la connexion à la base de données.
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function testConnection(): bool
    {
        try {
            $connection = $this->getConnection();
            $connection->connect();
            return $connection->isConnected();
        } catch (\Exception $e) {
            throw new DatabaseConnectionException(
                'Impossible de se connecter à la base de données : ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Récupère la liste de toutes les tables de la base de données.
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function listTables(): array
    {
        try {
            $connection = $this->getConnection();
            $schemaManager = $connection->createSchemaManager();
            
            $tables = $schemaManager->listTableNames();
            
            // Filtrer les tables système selon le type de base de données
            return array_filter($tables, [$this, 'isUserTable']);
            
        } catch (\Exception $e) {
            throw new DatabaseConnectionException(
                'Erreur lors de la récupération des tables : ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Analyse les tables spécifiées ou toutes les tables.
     *
     * @param array $includeTables Tables à inclure (toutes si vide)
     * @param array $excludeTables Tables à exclure
     * @return array
     * @throws DatabaseConnectionException
     */
    public function analyzeTables(array $includeTables = [], array $excludeTables = []): array
    {
        $allTables = $this->listTables();
        
        // Si des tables spécifiques sont demandées
        if (!empty($includeTables)) {
            $tables = array_intersect($allTables, $includeTables);
        } else {
            $tables = $allTables;
        }
        
        // Exclure les tables spécifiées
        if (!empty($excludeTables)) {
            $tables = array_diff($tables, $excludeTables);
        }
        
        return array_values($tables);
    }

    /**
     * Récupère les informations détaillées d'une table.
     *
     * @param string $tableName
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getTableDetails(string $tableName): array
    {
        try {
            $connection = $this->getConnection();
            $schemaManager = $connection->createSchemaManager();
            
            // Essayer d'abord avec le SchemaManager standard
            try {
                $table = $schemaManager->introspectTable($tableName);
                
                return [
                    'name' => $table->getName(),
                    'columns' => $this->getColumnsInfo($table),
                    'indexes' => $this->getIndexesInfo($table),
                    'foreign_keys' => $this->getForeignKeysInfo($table),
                    'primary_key' => $table->getPrimaryKey()?->getColumns() ?? [],
                ];
            } catch (\Doctrine\DBAL\Exception $doctrineException) {
                // Si Doctrine échoue à cause des types ENUM/SET, utiliser notre méthode alternative
                if (str_contains($doctrineException->getMessage(), 'Unknown database type enum') ||
                    str_contains($doctrineException->getMessage(), 'Unknown database type set')) {
                    return $this->getTableDetailsWithFallback($tableName);
                }
                throw $doctrineException;
            }
            
        } catch (\Exception $e) {
            throw new DatabaseConnectionException(
                "Erreur lors de l'analyse de la table '{$tableName}' : " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Récupère ou crée la connexion à la base de données.
     *
     * @return Connection
     * @throws DatabaseConnectionException
     */
    private function getConnection(): Connection
    {
        if ($this->connection === null) {
            try {
                // Enregistrer les types MySQL personnalisés
                MySQLTypeMapper::registerCustomTypes();
                
                $this->connection = DriverManager::getConnection($this->databaseConfig);
                
                // Configurer la plateforme pour les types MySQL
                $platform = $this->connection->getDatabasePlatform();
                MySQLTypeMapper::configurePlatform($platform);
                
            } catch (\Exception $e) {
                throw new DatabaseConnectionException(
                    'Impossible de créer la connexion à la base de données : ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
        
        return $this->connection;
    }

    /**
     * Vérifie si une table est une table utilisateur (non système).
     *
     * @param string $tableName
     * @return bool
     */
    private function isUserTable(string $tableName): bool
    {
        $systemTables = [
            // MySQL
            'information_schema',
            'performance_schema',
            'mysql',
            'sys',
            // PostgreSQL
            'pg_catalog',
            'information_schema',
            // SQLite
            'sqlite_master',
            'sqlite_sequence',
        ];
        
        foreach ($systemTables as $systemTable) {
            if (str_starts_with($tableName, $systemTable)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Extrait les informations des colonnes.
     *
     * @param \Doctrine\DBAL\Schema\Table $table
     * @return array
     */
    private function getColumnsInfo(\Doctrine\DBAL\Schema\Table $table): array
    {
        $columns = [];
        
        // Obtenir les informations détaillées des colonnes via SHOW COLUMNS
        $detailedColumns = $this->getDetailedColumnInfo($table->getName());
        
        foreach ($table->getColumns() as $column) {
            $columnName = $column->getName();
            $detailedInfo = $detailedColumns[$columnName] ?? [];
            
            $columns[] = [
                'name' => $columnName,
                'type' => $column->getType()->getName(),
                'raw_type' => $detailedInfo['Type'] ?? $column->getType()->getName(),
                'length' => $column->getLength(),
                'precision' => $column->getPrecision(),
                'scale' => $column->getScale(),
                'nullable' => !$column->getNotnull(),
                'default' => $column->getDefault(),
                'auto_increment' => $column->getAutoincrement(),
                'comment' => $column->getComment(),
                'enum_values' => $detailedInfo['enum_values'] ?? null,
                'set_values' => $detailedInfo['set_values'] ?? null,
            ];
        }
        
        return $columns;
    }

    /**
     * Extrait les informations des index.
     *
     * @param \Doctrine\DBAL\Schema\Table $table
     * @return array
     */
    private function getIndexesInfo(\Doctrine\DBAL\Schema\Table $table): array
    {
        $indexes = [];
        
        foreach ($table->getIndexes() as $index) {
            $indexes[] = [
                'name' => $index->getName(),
                'columns' => $index->getColumns(),
                'unique' => $index->isUnique(),
                'primary' => $index->isPrimary(),
            ];
        }
        
        return $indexes;
    }

    /**
     * Extrait les informations des clés étrangères.
     *
     * @param \Doctrine\DBAL\Schema\Table $table
     * @return array
     */
    private function getForeignKeysInfo(\Doctrine\DBAL\Schema\Table $table): array
    {
        $foreignKeys = [];
        
        foreach ($table->getForeignKeys() as $foreignKey) {
            $foreignKeys[] = [
                'name' => $foreignKey->getName(),
                'local_columns' => $foreignKey->getLocalColumns(),
                'foreign_table' => $foreignKey->getForeignTableName(),
                'foreign_columns' => $foreignKey->getForeignColumns(),
                'on_update' => $foreignKey->onUpdate() ?? 'RESTRICT',
                'on_delete' => $foreignKey->onDelete() ?? 'RESTRICT',
            ];
        }
        
        // Si aucune clé étrangère n'est trouvée via Doctrine, essayer la méthode fallback
        if (empty($foreignKeys)) {
            $foreignKeys = $this->getForeignKeysWithFallback($table->getName());
        }
        
        return $foreignKeys;
    }

    /**
     * Obtient les informations détaillées des colonnes via SHOW COLUMNS.
     *
     * @param string $tableName
     * @return array
     */
    private function getDetailedColumnInfo(string $tableName): array
    {
        try {
            $connection = $this->getConnection();
            $sql = "SHOW COLUMNS FROM `{$tableName}`";
            $result = $connection->executeQuery($sql);
            
            $columns = [];
            while ($row = $result->fetchAssociative()) {
                $columnInfo = [
                    'Field' => $row['Field'],
                    'Type' => $row['Type'],
                    'Null' => $row['Null'],
                    'Key' => $row['Key'],
                    'Default' => $row['Default'],
                    'Extra' => $row['Extra'],
                ];
                
                // Extraire les valeurs ENUM/SET
                if (preg_match('/^enum\((.+)\)$/i', $row['Type'], $matches)) {
                    $columnInfo['enum_values'] = MySQLTypeMapper::extractEnumValues($row['Type']);
                } elseif (preg_match('/^set\((.+)\)$/i', $row['Type'], $matches)) {
                    $columnInfo['set_values'] = MySQLTypeMapper::extractSetValues($row['Type']);
                }
                
                $columns[$row['Field']] = $columnInfo;
            }
            
            return $columns;
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide pour ne pas bloquer le processus
            return [];
        }
    }

    /**
     * Méthode de fallback pour obtenir les détails d'une table quand Doctrine échoue.
     *
     * @param string $tableName
     * @return array
     */
    private function getTableDetailsWithFallback(string $tableName): array
    {
        $connection = $this->getConnection();
        
        // Obtenir les informations des colonnes via SHOW COLUMNS
        $detailedColumns = $this->getDetailedColumnInfo($tableName);
        
        // Construire les informations des colonnes
        $columns = [];
        foreach ($detailedColumns as $columnName => $columnInfo) {
            $type = $this->mapMySQLTypeToDoctrineType($columnInfo['Type']);
            
            $columns[] = [
                'name' => $columnName,
                'type' => $type,
                'raw_type' => $columnInfo['Type'],
                'length' => $this->extractLength($columnInfo['Type']),
                'precision' => null,
                'scale' => null,
                'nullable' => $columnInfo['Null'] === 'YES',
                'default' => $columnInfo['Default'],
                'auto_increment' => str_contains($columnInfo['Extra'], 'auto_increment'),
                'comment' => '',
                'enum_values' => $columnInfo['enum_values'] ?? null,
                'set_values' => $columnInfo['set_values'] ?? null,
            ];
        }
        
        // Obtenir les clés primaires
        $primaryKey = [];
        foreach ($detailedColumns as $columnName => $columnInfo) {
            if ($columnInfo['Key'] === 'PRI') {
                $primaryKey[] = $columnName;
            }
        }
        
        // Obtenir les clés étrangères via INFORMATION_SCHEMA
        $foreignKeys = $this->getForeignKeysWithFallback($tableName);
        
        // Obtenir les index via SHOW INDEX
        $indexes = $this->getIndexesWithFallback($tableName);
        
        return [
            'name' => $tableName,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
            'primary_key' => $primaryKey,
        ];
    }

    /**
     * Mappe un type MySQL vers un type Doctrine.
     */
    private function mapMySQLTypeToDoctrineType(string $mysqlType): string
    {
        // Nettoyer le type en supprimant les modificateurs comme 'unsigned'
        $cleanType = preg_replace('/\s+(unsigned|signed|zerofill)/i', '', $mysqlType);
        $baseType = strtolower(explode('(', $cleanType)[0]);
        
        return match ($baseType) {
            'tinyint', 'smallint', 'mediumint', 'int', 'integer' => 'integer',
            'bigint' => 'bigint',
            'decimal', 'numeric' => 'decimal',
            'float', 'double', 'real' => 'float',
            'bit', 'boolean', 'bool' => 'boolean',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            'time' => 'time',
            'year' => 'integer',
            'char', 'varchar' => 'string',
            'text', 'tinytext', 'mediumtext', 'longtext' => 'text',
            'binary', 'varbinary' => 'binary',
            'blob', 'tinyblob', 'mediumblob', 'longblob' => 'blob',
            'json' => 'json',
            'enum', 'set' => 'string',
            default => 'string',
        };
    }

    /**
     * Extrait la longueur d'un type MySQL.
     */
    private function extractLength(string $mysqlType): ?int
    {
        if (preg_match('/\((\d+)\)/', $mysqlType, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Obtient les clés étrangères via INFORMATION_SCHEMA.
     */
    private function getForeignKeysWithFallback(string $tableName): array
    {
        try {
            $connection = $this->getConnection();
            $sql = "
                SELECT
                    kcu.CONSTRAINT_NAME,
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.UPDATE_RULE,
                    rc.DELETE_RULE
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
                    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                    AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
                WHERE kcu.TABLE_SCHEMA = DATABASE()
                AND kcu.TABLE_NAME = ?
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
            ";
            
            $result = $connection->executeQuery($sql, [$tableName]);
            $foreignKeys = [];
            $groupedKeys = [];
            
            while ($row = $result->fetchAssociative()) {
                $constraintName = $row['CONSTRAINT_NAME'];
                if (!isset($groupedKeys[$constraintName])) {
                    $groupedKeys[$constraintName] = [
                        'name' => $constraintName,
                        'local_columns' => [],
                        'foreign_table' => $row['REFERENCED_TABLE_NAME'],
                        'foreign_columns' => [],
                        'on_update' => $row['UPDATE_RULE'] ?? 'RESTRICT',
                        'on_delete' => $row['DELETE_RULE'] ?? 'RESTRICT',
                    ];
                }
                
                $groupedKeys[$constraintName]['local_columns'][] = $row['COLUMN_NAME'];
                $groupedKeys[$constraintName]['foreign_columns'][] = $row['REFERENCED_COLUMN_NAME'];
            }
            
            return array_values($groupedKeys);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtient les index via SHOW INDEX.
     */
    private function getIndexesWithFallback(string $tableName): array
    {
        try {
            $connection = $this->getConnection();
            $sql = "SHOW INDEX FROM `{$tableName}`";
            $result = $connection->executeQuery($sql);
            
            $indexes = [];
            $indexData = [];
            
            while ($row = $result->fetchAssociative()) {
                $indexName = $row['Key_name'];
                if (!isset($indexData[$indexName])) {
                    $indexData[$indexName] = [
                        'name' => $indexName,
                        'columns' => [],
                        'unique' => $row['Non_unique'] == 0,
                        'primary' => $indexName === 'PRIMARY',
                    ];
                }
                $indexData[$indexName]['columns'][] = $row['Column_name'];
            }
            
            foreach ($indexData as $index) {
                $indexes[] = $index;
            }
            
            return $indexes;
        } catch (\Exception $e) {
            return [];
        }
    }
}