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
    public function __construct(
        private readonly Environment $twig,
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
            ];

            // Generate repository if requested
            if ($options['generate_repository'] ?? $this->config['generate_repository'] ?? true) {
                $result['repository'] = $this->generateRepository($metadata, $options);
            }

            return $result;
        } catch (Exception $e) {
            throw new EntityGenerationException(
                "Error generating entity for table '{$tableName}': " . $e->getMessage(),
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

        $properties = $this->prepareProperties($metadata['columns'], $metadata['primary_key']);

        $namespace = $options['namespace'] ?? $this->config['namespace'] ?? 'App\\Entity';

        return [
            'entity_name'     => $metadata['entity_name'],
            'table_name'      => $metadata['table_name'],
            'namespace'       => $namespace,
            'repository_name' => $metadata['repository_name'],
            'use_annotations' => $useAnnotations,
            'properties'      => $properties,
            'relations'       => $this->prepareRelations($metadata['relations'], $namespace),
            'indexes'         => $metadata['indexes'],
            'imports'         => $this->generateImports($metadata, $useAnnotations),
            'constants'       => $this->generateConstants($properties),
        ];
    }

    /**
     * Prepares entity properties.
     */
    private function prepareProperties(array $columns, array $primaryKey): array
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
            ];

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
    private function generateImports(array $metadata, bool $useAnnotations): array
    {
        $imports = [];

        if ($useAnnotations) {
            $imports[] = 'Doctrine\\ORM\\Mapping as ORM';
        } else {
            $imports[] = 'Doctrine\\ORM\\Mapping as ORM';
            $imports[] = 'Doctrine\\ORM\\Mapping\\Entity';
            $imports[] = 'Doctrine\\ORM\\Mapping\\Table';
            $imports[] = 'Doctrine\\ORM\\Mapping\\Column';
            $imports[] = 'Doctrine\\ORM\\Mapping\\Id';
            $imports[] = 'Doctrine\\ORM\\Mapping\\GeneratedValue';
        }

        // Add imports for date types
        foreach ($metadata['columns'] as $column) {
            if ($column['type'] === '\DateTimeInterface') {
                $imports[] = 'DateTimeInterface';
                break;
            }
        }

        // Add imports for relations
        if (! empty($metadata['relations'])) {
            if (! $useAnnotations) {
                $imports[] = 'Doctrine\\ORM\\Mapping\\ManyToOne';
                $imports[] = 'Doctrine\\ORM\\Mapping\\OneToMany';
                $imports[] = 'Doctrine\\ORM\\Mapping\\JoinColumn';
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
                'Error rendering entity template: ' . $e->getMessage(),
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
}
