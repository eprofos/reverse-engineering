<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MetadataExtractionException;
use Exception;

use function in_array;

/**
 * Service pour l'extraction des métadonnées des tables.
 */
class MetadataExtractor
{
    public function __construct(
        private readonly DatabaseAnalyzer $databaseAnalyzer,
    ) {
    }

    /**
     * Extrait les métadonnées complètes d'une table.
     *
     * @param array $allTables Liste de toutes les tables pour détecter les relations inverses
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
                "Erreur lors de l'extraction des métadonnées de la table '{$tableName}' : " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Traite les colonnes et les convertit en propriétés d'entité.
     */
    private function processColumns(array $columns, array $foreignKeys = [], array $primaryKey = []): array
    {
        $processedColumns  = [];
        $foreignKeyColumns = [];

        // Extraire les colonnes qui sont des clés étrangères
        foreach ($foreignKeys as $fk) {
            foreach ($fk['local_columns'] as $localColumn) {
                $foreignKeyColumns[] = $localColumn;
            }
        }

        foreach ($columns as $column) {
            // Utiliser le type brut si disponible, sinon le type Doctrine
            $typeToMap   = $column['raw_type'] ?? $column['type'];
            $basePhpType = $this->mapDatabaseTypeToPhp($typeToMap);

            // Ajouter le préfixe ? pour les types nullable (sauf bool et clés primaires)
            $phpType      = $basePhpType;
            $isPrimaryKey = in_array($column['name'], $primaryKey, true);

            // Les clés primaires et colonnes NOT NULL ne doivent pas être nullable
            // Exception : les types DateTime peuvent être nullable même s'ils ne sont pas explicitement NULL
            if ($column['nullable'] && ! $isPrimaryKey && $basePhpType !== 'bool') {
                // Gérer les types avec namespace (commençant par \)
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
            ];

            // Ajouter les informations ENUM/SET si disponibles
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
            $localColumn  = $foreignKey['local_columns'][0]; // Prendre la première colonne locale

            // Générer un nom de propriété unique
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
     * Traite les index de la table.
     */
    private function processIndexes(array $indexes): array
    {
        $processedIndexes = [];

        foreach ($indexes as $index) {
            if (! $index['primary']) { // Exclure la clé primaire
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
     * Génère le nom de l'entité à partir du nom de la table.
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
        } elseif (str_ends_with($entityName, 's') && ! str_ends_with($entityName, 'ss')) {
            $entityName = substr($entityName, 0, -1);
        }

        return $entityName;
    }

    /**
     * Génère le nom de la propriété à partir du nom de la colonne.
     */
    private function generatePropertyName(string $columnName): string
    {
        // Convertir snake_case en camelCase
        $parts        = explode('_', $columnName);
        $propertyName = array_shift($parts);

        foreach ($parts as $part) {
            $propertyName .= ucfirst($part);
        }

        return $propertyName;
    }

    /**
     * Génère le nom de la propriété de relation.
     */
    private function generateRelationPropertyName(string $tableName): string
    {
        $entityName = $this->generateEntityName($tableName);

        return lcfirst($entityName);
    }

    /**
     * Génère un nom de propriété de relation unique en tenant compte des conflits.
     */
    private function generateUniqueRelationPropertyName(string $targetTable, string $localColumn, array $usedPropertyNames): string
    {
        // Nom de base basé sur la table cible
        $basePropertyName = $this->generateRelationPropertyName($targetTable);

        // Si le nom de base n'est pas utilisé, le retourner
        if (! in_array($basePropertyName, $usedPropertyNames, true)) {
            return $basePropertyName;
        }

        // Sinon, générer un nom basé sur la colonne locale
        $columnBasedName = $this->generatePropertyNameFromColumn($localColumn, $targetTable);

        // Si ce nom n'est pas utilisé, le retourner
        if (! in_array($columnBasedName, $usedPropertyNames, true)) {
            return $columnBasedName;
        }

        // En dernier recours, ajouter un suffixe numérique
        $counter    = 2;
        $uniqueName = $basePropertyName . $counter;

        while (in_array($uniqueName, $usedPropertyNames, true)) {
            ++$counter;
            $uniqueName = $basePropertyName . $counter;
        }

        return $uniqueName;
    }

    /**
     * Génère un nom de propriété basé sur la colonne locale et la table cible.
     */
    private function generatePropertyNameFromColumn(string $localColumn, string $targetTable): string
    {
        // Supprimer le suffixe '_id' de la colonne locale
        $columnWithoutId = preg_replace('/_id$/', '', $localColumn);

        // Si la colonne contient le nom de la table cible, utiliser la colonne directement
        $targetEntityLower = strtolower($this->generateEntityName($targetTable));

        if (str_contains(strtolower($columnWithoutId), $targetEntityLower)) {
            return $this->generatePropertyName($columnWithoutId);
        }

        // Sinon, combiner la colonne avec l'entité cible
        $propertyName = $this->generatePropertyName($columnWithoutId);
        $targetEntity = $this->generateEntityName($targetTable);

        // Si la propriété ne contient pas déjà le nom de l'entité, l'ajouter
        if (stripos($propertyName, $targetEntity) === false) {
            $propertyName .= $targetEntity;
        }

        return $propertyName;
    }

    /**
     * Génère le nom du repository.
     */
    private function generateRepositoryName(string $tableName): string
    {
        return $this->generateEntityName($tableName) . 'Repository';
    }

    /**
     * Mappe les types de base de données vers les types PHP.
     */
    private function mapDatabaseTypeToPhp(string $databaseType): string
    {
        // Nettoyer le type en supprimant les modificateurs comme 'unsigned'
        $cleanType = preg_replace('/\s+(unsigned|signed|zerofill)/i', '', $databaseType);
        // Extraire le type de base (sans les paramètres)
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
     * Mappe les types de base de données vers les types Doctrine.
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
     * Détermine si une relation est nullable en fonction des colonnes locales.
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
     * Améliore le commentaire d'une colonne avec les valeurs ENUM.
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
     * Améliore le commentaire d'une colonne avec les valeurs SET.
     */
    private function enhanceCommentWithSetValues(?string $originalComment, array $setValues): string
    {
        $setComment = 'Possible SET values: ' . implode(', ', array_map(fn ($v) => "'{$v}'", $setValues));

        if ($originalComment) {
            return $originalComment . ' - ' . $setComment;
        }

        return $setComment;
    }
}
