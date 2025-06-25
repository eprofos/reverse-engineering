<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MetadataExtractionException;

/**
 * Service pour l'extraction des métadonnées des tables.
 */
class MetadataExtractor
{
    public function __construct(
        private readonly DatabaseAnalyzer $databaseAnalyzer
    ) {
    }

    /**
     * Extrait les métadonnées complètes d'une table.
     *
     * @param string $tableName
     * @param array $allTables Liste de toutes les tables pour détecter les relations inverses
     * @return array
     * @throws MetadataExtractionException
     */
    public function extractTableMetadata(string $tableName, array $allTables = []): array
    {
        try {
            $tableDetails = $this->databaseAnalyzer->getTableDetails($tableName);
            $processedColumns = $this->processColumns($tableDetails['columns'], $tableDetails['foreign_keys']);
            
            return [
                'table_name' => $tableName,
                'entity_name' => $this->generateEntityName($tableName),
                'columns' => $processedColumns,
                'relations' => $this->extractRelations($tableDetails, $allTables),
                'indexes' => $this->processIndexes($tableDetails['indexes']),
                'primary_key' => $tableDetails['primary_key'],
                'repository_name' => $this->generateRepositoryName($tableName),
            ];
            
        } catch (\Exception $e) {
            throw new MetadataExtractionException(
                "Erreur lors de l'extraction des métadonnées de la table '{$tableName}' : " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Traite les colonnes et les convertit en propriétés d'entité.
     *
     * @param array $columns
     * @param array $foreignKeys
     * @return array
     */
    private function processColumns(array $columns, array $foreignKeys = []): array
    {
        $processedColumns = [];
        $foreignKeyColumns = [];
        
        // Extraire les colonnes qui sont des clés étrangères
        foreach ($foreignKeys as $fk) {
            foreach ($fk['local_columns'] as $localColumn) {
                $foreignKeyColumns[] = $localColumn;
            }
        }
        
        foreach ($columns as $column) {
            $processedColumns[] = [
                'name' => $column['name'],
                'property_name' => $this->generatePropertyName($column['name']),
                'type' => $this->mapDatabaseTypeToPhp($column['type']),
                'doctrine_type' => $this->mapDatabaseTypeToDoctrineType($column['type']),
                'nullable' => $column['nullable'],
                'length' => $column['length'],
                'precision' => $column['precision'],
                'scale' => $column['scale'],
                'default' => $column['default'],
                'auto_increment' => $column['auto_increment'],
                'comment' => $column['comment'],
                'is_primary' => false, // Sera mis à jour plus tard
                'is_foreign_key' => in_array($column['name'], $foreignKeyColumns),
            ];
        }
        
        return $processedColumns;
    }

    /**
     * Extrait les relations entre les tables.
     *
     * @param array $tableDetails
     * @param array $allTables
     * @return array
     */
    private function extractRelations(array $tableDetails, array $allTables = []): array
    {
        $relations = [];
        
        // Relations basées sur les clés étrangères (ManyToOne)
        foreach ($tableDetails['foreign_keys'] as $foreignKey) {
            $targetTable = $foreignKey['foreign_table'];
            $targetEntity = $this->generateEntityName($targetTable);
            
            $relations[] = [
                'type' => 'many_to_one',
                'target_entity' => $targetEntity,
                'target_table' => $targetTable,
                'local_columns' => $foreignKey['local_columns'],
                'foreign_columns' => $foreignKey['foreign_columns'],
                'property_name' => $this->generateRelationPropertyName($targetTable),
                'on_delete' => $foreignKey['on_delete'],
                'on_update' => $foreignKey['on_update'],
                'nullable' => $this->isRelationNullable($foreignKey['local_columns'], $tableDetails['columns']),
            ];
        }
        
        return $relations;
    }

    /**
     * Traite les index de la table.
     *
     * @param array $indexes
     * @return array
     */
    private function processIndexes(array $indexes): array
    {
        $processedIndexes = [];
        
        foreach ($indexes as $index) {
            if (!$index['primary']) { // Exclure la clé primaire
                $processedIndexes[] = [
                    'name' => $index['name'],
                    'columns' => $index['columns'],
                    'unique' => $index['unique'],
                ];
            }
        }
        
        return $processedIndexes;
    }

    /**
     * Génère le nom de l'entité à partir du nom de la table.
     *
     * @param string $tableName
     * @return string
     */
    private function generateEntityName(string $tableName): string
    {
        // Convertir snake_case en PascalCase
        $entityName = str_replace('_', ' ', $tableName);
        $entityName = ucwords($entityName);
        $entityName = str_replace(' ', '', $entityName);
        
        // Singulariser si nécessaire (règles basiques)
        if (str_ends_with($entityName, 'ies')) {
            $entityName = substr($entityName, 0, -3) . 'y';
        } elseif (str_ends_with($entityName, 's') && !str_ends_with($entityName, 'ss')) {
            $entityName = substr($entityName, 0, -1);
        }
        
        return $entityName;
    }

    /**
     * Génère le nom de la propriété à partir du nom de la colonne.
     *
     * @param string $columnName
     * @return string
     */
    private function generatePropertyName(string $columnName): string
    {
        // Convertir snake_case en camelCase
        $parts = explode('_', $columnName);
        $propertyName = array_shift($parts);
        
        foreach ($parts as $part) {
            $propertyName .= ucfirst($part);
        }
        
        return $propertyName;
    }

    /**
     * Génère le nom de la propriété de relation.
     *
     * @param string $tableName
     * @return string
     */
    private function generateRelationPropertyName(string $tableName): string
    {
        $entityName = $this->generateEntityName($tableName);
        return lcfirst($entityName);
    }

    /**
     * Génère le nom du repository.
     *
     * @param string $tableName
     * @return string
     */
    private function generateRepositoryName(string $tableName): string
    {
        return $this->generateEntityName($tableName) . 'Repository';
    }

    /**
     * Mappe les types de base de données vers les types PHP.
     *
     * @param string $databaseType
     * @return string
     */
    private function mapDatabaseTypeToPhp(string $databaseType): string
    {
        return match (strtolower($databaseType)) {
            'int', 'integer' => 'int',
            'bigint' => 'int',
            'smallint' => 'int',
            'tinyint' => 'int',
            'float', 'double', 'real' => 'float',
            'decimal', 'numeric' => 'string',
            'boolean', 'bool' => 'bool',
            'date', 'datetime', 'timestamp' => '\DateTimeInterface',
            'time' => '\DateTimeInterface',
            'json' => 'array',
            'text', 'longtext', 'mediumtext', 'tinytext' => 'string',
            'varchar', 'char' => 'string',
            'blob', 'longblob', 'mediumblob', 'tinyblob' => 'string',
            'binary', 'varbinary' => 'string',
            'uuid' => 'string',
            default => 'string',
        };
    }

    /**
     * Mappe les types de base de données vers les types Doctrine.
     *
     * @param string $databaseType
     * @return string
     */
    private function mapDatabaseTypeToDoctrineType(string $databaseType): string
    {
        return match (strtolower($databaseType)) {
            'int', 'integer' => 'integer',
            'bigint' => 'bigint',
            'smallint' => 'smallint',
            'tinyint' => 'smallint',
            'float', 'double', 'real' => 'float',
            'decimal', 'numeric' => 'decimal',
            'boolean', 'bool' => 'boolean',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            'time' => 'time',
            'json' => 'json',
            'text', 'longtext', 'mediumtext', 'tinytext' => 'text',
            'varchar', 'char' => 'string',
            'blob', 'longblob', 'mediumblob', 'tinyblob' => 'blob',
            'binary', 'varbinary' => 'binary',
            'uuid' => 'uuid',
            default => 'string',
        };
    }

    /**
     * Détermine si une relation est nullable en fonction des colonnes locales.
     *
     * @param array $localColumns
     * @param array $tableColumns
     * @return bool
     */
    private function isRelationNullable(array $localColumns, array $tableColumns): bool
    {
        foreach ($localColumns as $localColumn) {
            foreach ($tableColumns as $column) {
                if ($column['name'] === $localColumn) {
                    return $column['nullable'];
                }
            }
        }
        
        return false;
    }
}