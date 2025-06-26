<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\DatabaseConnectionException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

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
            
            $table = $schemaManager->introspectTable($tableName);
            
            return [
                'name' => $table->getName(),
                'columns' => $this->getColumnsInfo($table),
                'indexes' => $this->getIndexesInfo($table),
                'foreign_keys' => $this->getForeignKeysInfo($table),
                'primary_key' => $table->getPrimaryKey()?->getColumns() ?? [],
            ];
            
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
                $this->connection = DriverManager::getConnection($this->databaseConfig);
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
        
        foreach ($table->getColumns() as $column) {
            $columns[] = [
                'name' => $column->getName(),
                'type' => $column->getType()->getName(),
                'length' => $column->getLength(),
                'precision' => $column->getPrecision(),
                'scale' => $column->getScale(),
                'nullable' => !$column->getNotnull(),
                'default' => $column->getDefault(),
                'auto_increment' => $column->getAutoincrement(),
                'comment' => $column->getComment(),
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
                'on_update' => $foreignKey->onUpdate(),
                'on_delete' => $foreignKey->onDelete(),
            ];
        }
        
        return $foreignKeys;
    }
}