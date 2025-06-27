<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle\Service;

use Eprofos\ReverseEngineeringBundle\Exception\MetadataExtractionException;
use Exception;

use function in_array;

/**
 * Service for extracting table metadata.
 */
class MetadataExtractor
{
    public function __construct(
        private readonly DatabaseAnalyzer $databaseAnalyzer,
    ) {
    }

    /**
     * Extracts complete metadata from a table.
     *
     * @param array $allTables List of all tables to detect inverse relations
     *
     * @throws MetadataExtractionException
     */
    public function extractTableMetadata(string $tableName, array $allTables = []): array
    {
        try {
            $tableDetails     = $this->databaseAnalyzer->getTableDetails($tableName);
            $processedColumns = $this->processColumns($tableDetails['columns'], $tableDetails['foreign_keys'], $tableDetails['primary_key']);

            return [
                'table_name'      => $tableName,
                'entity_name'     => $this->generateEntityName($tableName),
                'columns'         => $processedColumns,
                'relations'       => $this->extractRelations($tableDetails, $allTables),
                'indexes'         => $this->processIndexes($tableDetails['indexes']),
                'primary_key'     => $tableDetails['primary_key'],
                'repository_name' => $this->generateRepositoryName($tableName),
            ];
        } catch (Exception $e) {
            throw new MetadataExtractionException(
                "Metadata extraction failed for table '{$tableName}': " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Processes columns and converts them to entity properties.
     */
    private function processColumns(array $columns, array $foreignKeys = [], array $primaryKey = []): array
    {
        $processedColumns  = [];
        $foreignKeyColumns = [];

        // Extract columns that are foreign keys
        foreach ($foreignKeys as $fk) {
            foreach ($fk['local_columns'] as $localColumn) {
                $foreignKeyColumns[] = $localColumn;
            }
        }

        foreach ($columns as $column) {
            // Use raw type if available, otherwise Doctrine type
            $typeToMap   = $column['raw_type'] ?? $column['type'];
            $basePhpType = $this->mapDatabaseTypeToPhp($typeToMap);

            // Add ? prefix for nullable types (except bool and primary keys)
            $phpType      = $basePhpType;
            $isPrimaryKey = in_array($column['name'], $primaryKey, true);

            // Primary keys and NOT NULL columns should not be nullable
            // Exception: DateTime types can be nullable even if not explicitly NULL
            if ($column['nullable'] && ! $isPrimaryKey && $basePhpType !== 'bool') {
                // Handle types with namespace (starting with \)
                $phpType = '?' . $basePhpType;
            }

            $processedColumn = [
                'name'           => $column['name'],
                'property_name'  => $this->generatePropertyName($column['name']),
                'type'           => $phpType,
                'doctrine_type'  => $this->mapDatabaseTypeToDoctrineType($column['type']),
                'nullable'       => $column['nullable'],
                'length'         => $column['length'],
                'precision'      => $column['precision'],
                'scale'          => $column['scale'],
                'default'        => $column['default'],
                'auto_increment' => $column['auto_increment'],
                'comment'        => $column['comment'],
                'is_primary'     => $isPrimaryKey,
                'is_foreign_key' => in_array($column['name'], $foreignKeyColumns, true),
                'needs_lifecycle_callback' => $this->needsLifecycleCallback($column),
            ];

            // Add ENUM/SET information if available
            if (isset($column['enum_values'])) {
                $processedColumn['enum_values'] = $column['enum_values'];
                $processedColumn['comment']     = $this->enhanceCommentWithEnumValues(
                    $column['comment'],
                    $column['enum_values'],
                );
            }

            if (isset($column['set_values'])) {
                $processedColumn['set_values'] = $column['set_values'];
                $processedColumn['comment']    = $this->enhanceCommentWithSetValues(
                    $column['comment'],
                    $column['set_values'],
                );
            }

            $processedColumns[] = $processedColumn;
        }

        return $processedColumns;
    }

    /**
     * Extracts relationships between tables.
     */
    private function extractRelations(array $tableDetails, array $allTables = []): array
    {
        $relations         = [];
        $usedPropertyNames = [];

        // Relations based on foreign keys (ManyToOne)
        foreach ($tableDetails['foreign_keys'] as $foreignKey) {
            $targetTable  = $foreignKey['foreign_table'];
            $targetEntity = $this->generateEntityName($targetTable);
            $localColumn  = $foreignKey['local_columns'][0]; // Take the first local column

            // Generate unique property name
            $propertyName = $this->generateUniqueRelationPropertyName(
                $targetTable,
                $localColumn,
                $usedPropertyNames,
            );
            $usedPropertyNames[] = $propertyName;

            $relations[] = [
                'type'            => 'many_to_one',
                'target_entity'   => $targetEntity,
                'target_table'    => $targetTable,
                'local_columns'   => $foreignKey['local_columns'],
                'foreign_columns' => $foreignKey['foreign_columns'],
                'property_name'   => $propertyName,
                'on_delete'       => $foreignKey['on_delete'],
                'on_update'       => $foreignKey['on_update'],
                'nullable'        => $this->isRelationNullable($foreignKey['local_columns'], $tableDetails['columns']),
            ];
        }

        return $relations;
    }

    /**
     * Processes table indexes.
     */
    private function processIndexes(array $indexes): array
    {
        $processedIndexes = [];

        foreach ($indexes as $index) {
            if (! $index['primary']) { // Exclude primary key
                $processedIndexes[] = [
                    'name'    => $index['name'],
                    'columns' => $index['columns'],
                    'unique'  => $index['unique'],
                ];
            }
        }

        return $processedIndexes;
    }

    /**
     * Generates entity name from table name.
     */
    private function generateEntityName(string $tableName): string
    {
        // Convert snake_case to PascalCase
        $entityName = str_replace('_', ' ', $tableName);
        $entityName = ucwords($entityName);
        $entityName = str_replace(' ', '', $entityName);

        // Singularize if necessary (basic rules)
        if (str_ends_with($entityName, 'ies')) {
            $entityName = substr($entityName, 0, -3) . 'y';
        } elseif (str_ends_with($entityName, 's') && ! str_ends_with($entityName, 'ss')) {
            $entityName = substr($entityName, 0, -1);
        }

        return $entityName;
    }

    /**
     * Generates property name from column name.
     */
    private function generatePropertyName(string $columnName): string
    {
        // Convert snake_case to camelCase
        $parts        = explode('_', $columnName);
        $propertyName = array_shift($parts);

        foreach ($parts as $part) {
            $propertyName .= ucfirst($part);
        }

        return $propertyName;
    }

    /**
     * Generates relation property name.
     */
    private function generateRelationPropertyName(string $tableName): string
    {
        $entityName = $this->generateEntityName($tableName);

        return lcfirst($entityName);
    }

    /**
     * Generates unique relation property name considering conflicts.
     */
    private function generateUniqueRelationPropertyName(string $targetTable, string $localColumn, array $usedPropertyNames): string
    {
        // Base name based on target table
        $basePropertyName = $this->generateRelationPropertyName($targetTable);

        // If base name is not used, return it
        if (! in_array($basePropertyName, $usedPropertyNames, true)) {
            return $basePropertyName;
        }

        // Otherwise, generate name based on local column
        $columnBasedName = $this->generatePropertyNameFromColumn($localColumn, $targetTable);

        // If this name is not used, return it
        if (! in_array($columnBasedName, $usedPropertyNames, true)) {
            return $columnBasedName;
        }

        // As last resort, add numeric suffix
        $counter    = 2;
        $uniqueName = $basePropertyName . $counter;

        while (in_array($uniqueName, $usedPropertyNames, true)) {
            ++$counter;
            $uniqueName = $basePropertyName . $counter;
        }

        return $uniqueName;
    }

    /**
     * Generates property name based on local column and target table.
     */
    private function generatePropertyNameFromColumn(string $localColumn, string $targetTable): string
    {
        // Remove '_id' suffix from local column
        $columnWithoutId = preg_replace('/_id$/', '', $localColumn);

        // If column contains target table name, use column directly
        $targetEntityLower = strtolower($this->generateEntityName($targetTable));

        if (str_contains(strtolower($columnWithoutId), $targetEntityLower)) {
            return $this->generatePropertyName($columnWithoutId);
        }

        // Otherwise, combine column with target entity
        $propertyName = $this->generatePropertyName($columnWithoutId);
        $targetEntity = $this->generateEntityName($targetTable);

        // If property doesn't already contain entity name, add it
        if (stripos($propertyName, $targetEntity) === false) {
            $propertyName .= $targetEntity;
        }

        return $propertyName;
    }

    /**
     * Generates repository name.
     */
    private function generateRepositoryName(string $tableName): string
    {
        return $this->generateEntityName($tableName) . 'Repository';
    }

    /**
     * Maps database types to PHP types.
     */
    private function mapDatabaseTypeToPhp(string $databaseType): string
    {
        // Clean type by removing modifiers like 'unsigned'
        $cleanType = preg_replace('/\s+(unsigned|signed|zerofill)/i', '', $databaseType);
        // Extract base type (without parameters)
        $baseType = strtolower(explode('(', $cleanType)[0]);

        return match ($baseType) {
            'int', 'integer' => 'int',
            'bigint'    => 'int',
            'smallint'  => 'int',
            'mediumint' => 'int',
            'tinyint'   => 'int',
            'year'      => 'int',
            'float', 'double', 'real' => 'float',
            'decimal', 'numeric' => 'string',
            'boolean', 'bool' => 'bool',
            'bit' => 'bool',
            'date', 'datetime', 'timestamp' => '\DateTimeInterface',
            'time' => '\DateTimeInterface',
            'json' => 'array',
            'text', 'longtext', 'mediumtext', 'tinytext' => 'string',
            'varchar', 'char' => 'string',
            'blob', 'longblob', 'mediumblob', 'tinyblob' => 'string',
            'binary', 'varbinary' => 'string',
            'uuid'  => 'string',
            'enum'  => 'string',
            'set'   => 'string',
            default => 'string',
        };
    }

    /**
     * Maps database types to Doctrine types.
     */
    private function mapDatabaseTypeToDoctrineType(string $databaseType): string
    {
        return match (strtolower($databaseType)) {
            'int', 'integer' => 'integer',
            'bigint'   => 'bigint',
            'smallint' => 'smallint',
            'tinyint'  => 'smallint',
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
            'uuid'  => 'uuid',
            'enum'  => 'string',
            'set'   => 'string',
            default => 'string',
        };
    }

    /**
     * Determines if a relation is nullable based on local columns.
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

    /**
     * Enhances column comment with ENUM values.
     */
    private function enhanceCommentWithEnumValues(?string $originalComment, array $enumValues): string
    {
        $enumComment = 'Possible values: ' . implode(', ', array_map(fn ($v) => "'{$v}'", $enumValues));

        if ($originalComment) {
            return $originalComment . ' - ' . $enumComment;
        }

        return $enumComment;
    }

    /**
     * Enhances column comment with SET values.
     */
    private function enhanceCommentWithSetValues(?string $originalComment, array $setValues): string
    {
        $setComment = 'Possible SET values: ' . implode(', ', array_map(fn ($v) => "'{$v}'", $setValues));

        if ($originalComment) {
            return $originalComment . ' - ' . $setComment;
        }

        return $setComment;
    }

    /**
     * Determines if a column needs lifecycle callback for CURRENT_TIMESTAMP handling.
     */
    private function needsLifecycleCallback(array $column): bool
    {
        // Check if column has CURRENT_TIMESTAMP default and is a datetime/timestamp type
        if ($column['default'] === 'CURRENT_TIMESTAMP') {
            $doctrineType = $this->mapDatabaseTypeToDoctrineType($column['type']);
            return in_array($doctrineType, ['datetime', 'timestamp'], true);
        }

        return false;
    }
}
