<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\EntityGenerationException;
use Twig\Environment;

/**
 * Service pour la génération du code des entités Doctrine.
 */
class EntityGenerator
{
    public function __construct(
        private readonly Environment $twig,
        private readonly array $config = []
    ) {
    }

    /**
     * Génère une entité à partir des métadonnées d'une table.
     *
     * @param string $tableName
     * @param array $metadata
     * @param array $options
     * @return array
     * @throws EntityGenerationException
     */
    public function generateEntity(string $tableName, array $metadata, array $options = []): array
    {
        try {
            $entityData = $this->prepareEntityData($metadata, $options);
            $entityCode = $this->generateEntityCode($entityData);
            
            $namespace = $options['namespace'] ?? $this->config['namespace'] ?? 'App\\Entity';
            
            $result = [
                'name' => $metadata['entity_name'],
                'table' => $tableName,
                'namespace' => $namespace,
                'filename' => $metadata['entity_name'] . '.php',
                'code' => $entityCode,
                'properties' => $entityData['properties'],
                'relations' => $entityData['relations'],
            ];
            
            // Générer le repository si demandé
            if ($options['generate_repository'] ?? $this->config['generate_repository'] ?? true) {
                $result['repository'] = $this->generateRepository($metadata, $options);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            throw new EntityGenerationException(
                "Erreur lors de la génération de l'entité pour la table '{$tableName}' : " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Prépare les données pour la génération de l'entité.
     *
     * @param array $metadata
     * @param array $options
     * @return array
     */
    private function prepareEntityData(array $metadata, array $options): array
    {
        $useAnnotations = $options['use_annotations'] ?? $this->config['use_annotations'] ?? false;
        
        $properties = $this->prepareProperties($metadata['columns'], $metadata['primary_key']);
        
        $namespace = $options['namespace'] ?? $this->config['namespace'] ?? 'App\\Entity';
        
        return [
            'entity_name' => $metadata['entity_name'],
            'table_name' => $metadata['table_name'],
            'namespace' => $namespace,
            'repository_name' => $metadata['repository_name'],
            'use_annotations' => $useAnnotations,
            'properties' => $properties,
            'relations' => $this->prepareRelations($metadata['relations'], $namespace),
            'indexes' => $metadata['indexes'],
            'imports' => $this->generateImports($metadata, $useAnnotations),
            'constants' => $this->generateConstants($properties),
        ];
    }

    /**
     * Prépare les propriétés de l'entité.
     *
     * @param array $columns
     * @param array $primaryKey
     * @return array
     */
    private function prepareProperties(array $columns, array $primaryKey): array
    {
        $properties = [];
        
        foreach ($columns as $column) {
            // Exclure les colonnes qui sont des clés étrangères (elles seront gérées comme relations)
            if ($column['is_foreign_key']) {
                continue;
            }
            
            $property = [
                'name' => $column['property_name'],
                'column_name' => $column['name'],
                'type' => $column['type'],
                'doctrine_type' => $column['doctrine_type'],
                'nullable' => $column['nullable'],
                'length' => $column['length'],
                'precision' => $column['precision'],
                'scale' => $column['scale'],
                'default' => $column['default'],
                'auto_increment' => $column['auto_increment'],
                'comment' => $column['comment'],
                'is_primary' => in_array($column['name'], $primaryKey),
                'getter_name' => $this->generateGetterName($column['property_name']),
                'setter_name' => $this->generateSetterName($column['property_name']),
            ];
            
            $properties[] = $property;
        }
        
        return $properties;
    }

    /**
     * Prépare les relations de l'entité.
     *
     * @param array $relations
     * @param string $namespace
     * @return array
     */
    private function prepareRelations(array $relations, string $namespace): array
    {
        $preparedRelations = [];
        
        foreach ($relations as $relation) {
            $preparedRelations[] = [
                'type' => $relation['type'],
                'property_name' => $relation['property_name'],
                'target_entity' => $relation['target_entity'],
                'target_table' => $relation['target_table'] ?? null,
                'local_columns' => $relation['local_columns'],
                'foreign_columns' => $relation['foreign_columns'],
                'on_delete' => $relation['on_delete'],
                'on_update' => $relation['on_update'],
                'nullable' => $relation['nullable'] ?? true,
                'getter_name' => $this->generateGetterName($relation['property_name']),
                'setter_name' => $this->generateSetterName($relation['property_name']),
            ];
        }
        
        return $preparedRelations;
    }

    /**
     * Génère les imports nécessaires pour l'entité.
     *
     * @param array $metadata
     * @param bool $useAnnotations
     * @return array
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
        
        // Ajouter les imports pour les types de date
        foreach ($metadata['columns'] as $column) {
            if ($column['type'] === '\DateTimeInterface') {
                $imports[] = 'DateTimeInterface';
                break;
            }
        }
        
        // Ajouter les imports pour les relations
        if (!empty($metadata['relations'])) {
            if (!$useAnnotations) {
                $imports[] = 'Doctrine\\ORM\\Mapping\\ManyToOne';
                $imports[] = 'Doctrine\\ORM\\Mapping\\OneToMany';
                $imports[] = 'Doctrine\\ORM\\Mapping\\JoinColumn';
            }
        }
        
        return array_unique($imports);
    }

    /**
     * Génère le code de l'entité.
     *
     * @param array $entityData
     * @return string
     * @throws EntityGenerationException
     */
    private function generateEntityCode(array $entityData): string
    {
        try {
            return $this->twig->render('entity.php.twig', $entityData);
        } catch (\Exception $e) {
            throw new EntityGenerationException(
                'Erreur lors du rendu du template d\'entité : ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Génère le repository pour l'entité.
     *
     * @param array $metadata
     * @param array $options
     * @return array
     */
    private function generateRepository(array $metadata, array $options): array
    {
        $entityNamespace = $options['namespace'] ?? $this->config['namespace'] ?? 'App\\Entity';
        $repositoryNamespace = str_replace('\\Entity', '\\Repository', $entityNamespace);
        
        $repositoryData = [
            'repository_name' => $metadata['repository_name'],
            'entity_name' => $metadata['entity_name'],
            'namespace' => $repositoryNamespace,
            'entity_namespace' => $entityNamespace,
        ];
        
        $repositoryCode = $this->twig->render('repository.php.twig', $repositoryData);
        
        return [
            'name' => $metadata['repository_name'],
            'namespace' => $repositoryNamespace,
            'filename' => $metadata['repository_name'] . '.php',
            'entity_class' => $entityNamespace . '\\' . $metadata['entity_name'],
            'entity_namespace' => $entityNamespace,
            'code' => $repositoryCode,
        ];
    }

    /**
     * Génère le nom du getter pour une propriété.
     *
     * @param string $propertyName
     * @return string
     */
    private function generateGetterName(string $propertyName): string
    {
        return 'get' . ucfirst($propertyName);
    }

    /**
     * Génère le nom du setter pour une propriété.
     *
     * @param string $propertyName
     * @return string
     */
    private function generateSetterName(string $propertyName): string
    {
        return 'set' . ucfirst($propertyName);
    }

    /**
     * Génère les constantes pour les types ENUM/SET.
     *
     * @param array $properties
     * @return array
     */
    private function generateConstants(array $properties): array
    {
        $constants = [];
        
        foreach ($properties as $property) {
            // Générer des constantes pour les ENUM
            if (isset($property['enum_values']) && !empty($property['enum_values'])) {
                $enumConstants = \App\Service\MySQLTypeMapper::generateEnumConstants(
                    $property['enum_values'],
                    $property['name']
                );
                $constants = array_merge($constants, $enumConstants);
            }
            
            // Générer des constantes pour les SET
            if (isset($property['set_values']) && !empty($property['set_values'])) {
                $setConstants = \App\Service\MySQLTypeMapper::generateSetConstants(
                    $property['set_values'],
                    $property['name']
                );
                $constants = array_merge($constants, $setConstants);
            }
        }
        
        return $constants;
    }
}