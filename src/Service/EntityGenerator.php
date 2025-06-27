<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\EntityGenerationException;
use Exception;
use Twig\Environment;

use function in_array;

/**
 * Service for generating Doctrine entity code.
 */
class EntityGenerator
{
    private array $generatedEnumClasses = [];

    public function __construct(
        private readonly Environment $twig,
        private readonly EnumClassGenerator $enumClassGenerator,
        private readonly array $config = [],
    ) {
    }

    /**
     * Generates an entity from table metadata.
     *
     * @throws EntityGenerationException
     */
    public function generateEntity(string $tableName, array $metadata, array $options = []): array
    {
        try {
            $entityData = $this->prepareEntityData($metadata, $options);
            $entityCode = $this->generateEntityCode($entityData);

            $namespace = $options['namespace'] ?? $this->config['namespace'] ?? 'App\\Entity';

            $result = [
                'name'       => $metadata['entity_name'],
                'table'      => $tableName,
                'namespace'  => $namespace,
                'filename'   => $metadata['entity_name'] . '.php',
                'code'       => $entityCode,
                'properties' => $entityData['properties'],
                'relations'  => $entityData['relations'],
                'has_lifecycle_callbacks' => $entityData['has_lifecycle_callbacks'],
            ];

            // Generate repository if requested
            if ($options['generate_repository'] ?? $this->config['generate_repository'] ?? true) {
                $result['repository'] = $this->generateRepository($metadata, $options);
            }

            return $result;
        } catch (Exception $e) {
            throw new EntityGenerationException(
                "Entity generation failed for table '{$tableName}': " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Prepares data for entity generation.
     */
    private function prepareEntityData(array $metadata, array $options): array
    {
        $useAnnotations = $options['use_annotations'] ?? $this->config['use_annotations'] ?? false;

        $properties = $this->prepareProperties($metadata['columns'], $metadata['primary_key'], $metadata['table_name'] ?? 'unknown');

        $namespace = $options['namespace'] ?? $this->config['namespace'] ?? 'App\\Entity';
        
        // Check if any properties need lifecycle callbacks
        $hasLifecycleCallbacks = $this->hasLifecycleCallbacks($properties);
        
        // Generate base imports
        $imports = $this->generateImports($metadata, $useAnnotations, $hasLifecycleCallbacks);
        
        // Add repository import if repository is being generated
        $generateRepository = $options['generate_repository'] ?? $this->config['generate_repository'] ?? true;
        if ($generateRepository && !empty($metadata['repository_name'])) {
            $repositoryNamespace = str_replace('\\Entity', '\\Repository', $namespace);
            $imports[] = $repositoryNamespace . '\\' . $metadata['repository_name'];
        }

        return [
            'entity_name'     => $metadata['entity_name'],
            'table_name'      => $metadata['table_name'],
            'namespace'       => $namespace,
            'repository_name' => $metadata['repository_name'],
            'use_annotations' => $useAnnotations,
            'properties'      => $properties,
            'relations'       => $this->prepareRelations($metadata['relations'], $namespace),
            'indexes'         => $metadata['indexes'],
            'imports'         => array_unique($imports),
            'constants'       => $this->generateConstants($properties),
            'has_lifecycle_callbacks' => $hasLifecycleCallbacks,
        ];
    }

    /**
     * Prepares entity properties.
     */
    private function prepareProperties(array $columns, array $primaryKey, string $tableName = 'unknown'): array
    {
        $properties = [];

        foreach ($columns as $column) {
            // Exclude columns that are foreign keys (they will be handled as relations)
            if ($column['is_foreign_key']) {
                continue;
            }

            $property = [
                'name'           => $column['property_name'],
                'column_name'    => $column['name'],
                'type'           => $column['type'],
                'doctrine_type'  => $column['doctrine_type'],
                'nullable'       => $column['nullable'],
                'length'         => $column['length'],
                'precision'      => $column['precision'],
                'scale'          => $column['scale'],
                'default'        => $column['default'],
                'auto_increment' => $column['auto_increment'],
                'comment'        => $column['comment'],
                'is_primary'     => in_array($column['name'], $primaryKey, true),
                'getter_name'    => $this->generateGetterName($column['property_name']),
                'setter_name'    => $this->generateSetterName($column['property_name']),
                'needs_lifecycle_callback' => $column['needs_lifecycle_callback'] ?? false,
            ];

            // Handle ENUM columns - generate enum class and modify property
            if (isset($column['enum_values']) && !empty($column['enum_values'])) {
                $enumData = $this->generateEnumClassForProperty($column, $tableName);
                if ($enumData) {
                    $property['enum_class'] = $enumData['class_name'];
                    $property['enum_fqn'] = $enumData['fqn'];
                    $property['type'] = $enumData['class_name'];
                    $property['doctrine_type'] = 'string';
                    $property['enum_values'] = $column['enum_values'];
                    $property['has_enum_class'] = true;
                }
            }

            // Preserve enum_values for template compatibility
            if (isset($column['enum_values'])) {
                $property['enum_values'] = $column['enum_values'];
            }

            // Preserve set_values for template compatibility
            if (isset($column['set_values'])) {
                $property['set_values'] = $column['set_values'];
            }

            $properties[] = $property;
        }

        return $properties;
    }

    /**
     * Prepares entity relations.
     */
    private function prepareRelations(array $relations, string $namespace): array
    {
        $preparedRelations = [];

        foreach ($relations as $relation) {
            $preparedRelations[] = [
                'type'            => $relation['type'],
                'property_name'   => $relation['property_name'],
                'target_entity'   => $relation['target_entity'],
                'target_table'    => $relation['target_table'] ?? null,
                'local_columns'   => $relation['local_columns'],
                'foreign_columns' => $relation['foreign_columns'],
                'on_delete'       => $relation['on_delete'],
                'on_update'       => $relation['on_update'],
                'nullable'        => $relation['nullable'] ?? true,
                'getter_name'     => $this->generateGetterName($relation['property_name']),
                'setter_name'     => $this->generateSetterName($relation['property_name']),
            ];
        }

        return $preparedRelations;
    }

    /**
     * Generates necessary imports for the entity.
     */
    private function generateImports(array $metadata, bool $useAnnotations, bool $hasLifecycleCallbacks = false): array
    {
        $imports = [];

        // Always use the unified ORM import for both annotation and attribute modes
        $imports[] = 'Doctrine\\ORM\\Mapping as ORM';

        // Add imports for date types
        foreach ($metadata['columns'] as $column) {
            if ($column['type'] === '\DateTimeInterface') {
                $imports[] = 'DateTimeInterface';
                break;
            }
        }

        // Add DateTime import if lifecycle callbacks are needed
        if ($hasLifecycleCallbacks) {
            $imports[] = 'DateTime';
        }

        // Add enum class imports
        foreach ($metadata['columns'] as $column) {
            if (isset($column['enum_values']) && !empty($column['enum_values'])) {
                $tableName = $metadata['table_name'] ?? 'unknown';
                $enumClassName = $this->enumClassGenerator->generateEnumClassName($tableName, $column['name']);
                $enumFqn = $this->enumClassGenerator->getEnumFullyQualifiedName($enumClassName);
                $imports[] = $enumFqn;
            }
        }

        return array_unique($imports);
    }

    /**
     * Generates entity code.
     *
     * @throws EntityGenerationException
     */
    private function generateEntityCode(array $entityData): string
    {
        try {
            return $this->twig->render('entity.php.twig', $entityData);
        } catch (Exception $e) {
            throw new EntityGenerationException(
                'Entity template rendering failed: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Generates repository for the entity.
     */
    private function generateRepository(array $metadata, array $options): array
    {
        $entityNamespace     = $options['namespace'] ?? $this->config['namespace'] ?? 'App\\Entity';
        $repositoryNamespace = str_replace('\\Entity', '\\Repository', $entityNamespace);

        $repositoryData = [
            'repository_name'  => $metadata['repository_name'],
            'entity_name'      => $metadata['entity_name'],
            'namespace'        => $repositoryNamespace,
            'entity_namespace' => $entityNamespace,
        ];

        $repositoryCode = $this->twig->render('repository.php.twig', $repositoryData);

        return [
            'name'             => $metadata['repository_name'],
            'namespace'        => $repositoryNamespace,
            'filename'         => $metadata['repository_name'] . '.php',
            'entity_class'     => $entityNamespace . '\\' . $metadata['entity_name'],
            'entity_namespace' => $entityNamespace,
            'code'             => $repositoryCode,
        ];
    }

    /**
     * Generates getter name for a property.
     */
    private function generateGetterName(string $propertyName): string
    {
        return 'get' . ucfirst($propertyName);
    }

    /**
     * Generates setter name for a property.
     */
    private function generateSetterName(string $propertyName): string
    {
        return 'set' . ucfirst($propertyName);
    }

    /**
     * Generates constants for ENUM/SET types.
     */
    private function generateConstants(array $properties): array
    {
        $constants = [];

        foreach ($properties as $property) {
            // Skip generating constants if an enum class is being generated for this property
            if (isset($property['has_enum_class']) && $property['has_enum_class']) {
                continue;
            }

            // Generate constants for ENUM
            if (isset($property['enum_values']) && ! empty($property['enum_values'])) {
                $enumConstants = MySQLTypeMapper::generateEnumConstants(
                    $property['enum_values'],
                    $property['name'],
                );
                $constants = array_merge($constants, $enumConstants);
            }

            // Generate constants for SET
            if (isset($property['set_values']) && ! empty($property['set_values'])) {
                $setConstants = MySQLTypeMapper::generateSetConstants(
                    $property['set_values'],
                    $property['name'],
                );
                $constants = array_merge($constants, $setConstants);
            }
        }

        return $constants;
    }

    /**
     * Checks if any properties need lifecycle callbacks.
     */
    private function hasLifecycleCallbacks(array $properties): bool
    {
        foreach ($properties as $property) {
            if ($property['needs_lifecycle_callback'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates enum class for a property with ENUM values.
     */
    private function generateEnumClassForProperty(array $column, string $tableName): ?array
    {
        if (!isset($column['enum_values']) || empty($column['enum_values'])) {
            return null;
        }

        $columnName = $column['name'];
        
        // Generate enum class name
        $enumClassName = $this->enumClassGenerator->generateEnumClassName($tableName, $columnName);
        
        // Check if we've already generated this enum class
        $enumKey = $tableName . '.' . $columnName;
        if (isset($this->generatedEnumClasses[$enumKey])) {
            return $this->generatedEnumClasses[$enumKey];
        }
        
        try {
            // Generate enum content
            $enumContent = $this->enumClassGenerator->generateEnumContent(
                $enumClassName,
                $column['enum_values'],
                $tableName,
                $columnName
            );
            
            // Write enum file
            $this->enumClassGenerator->writeEnumFile($enumClassName, $enumContent, true);
            
            // Get fully qualified name
            $enumFqn = $this->enumClassGenerator->getEnumFullyQualifiedName($enumClassName);
            
            $enumData = [
                'class_name' => $enumClassName,
                'fqn' => $enumFqn,
                'file_path' => $this->enumClassGenerator->getEnumFilePath($enumClassName),
            ];
            
            // Cache the generated enum class
            $this->generatedEnumClasses[$enumKey] = $enumData;
            
            return $enumData;
        } catch (Exception $e) {
            // Log error but don't fail entity generation
            error_log("Failed to generate enum class for {$tableName}.{$columnName}: " . $e->getMessage());
            return null;
        }
    }
}
