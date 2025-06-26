<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\StringType;

/**
 * Service pour mapper les types MySQL spéciaux vers des types Doctrine supportés.
 */
class MySQLTypeMapper
{
    /**
     * Enregistre les types MySQL personnalisés.
     */
    public static function registerCustomTypes(): void
    {
        // Enregistrer le type ENUM comme STRING
        if (!Type::hasType('enum')) {
            Type::addType('enum', StringType::class);
        }
        
        // Enregistrer le type SET comme STRING
        if (!Type::hasType('set')) {
            Type::addType('set', StringType::class);
        }
    }
    
    /**
     * Configure la plateforme pour mapper les types MySQL.
     */
    public static function configurePlatform(AbstractPlatform $platform): void
    {
        // Mapper ENUM vers STRING
        $platform->registerDoctrineTypeMapping('enum', 'string');
        
        // Mapper SET vers STRING
        $platform->registerDoctrineTypeMapping('set', 'string');
        
        // Autres mappings MySQL utiles
        $platform->registerDoctrineTypeMapping('year', 'integer');
        $platform->registerDoctrineTypeMapping('bit', 'boolean');
    }
    
    /**
     * Extrait les valeurs d'un type ENUM depuis sa définition.
     */
    public static function extractEnumValues(string $enumDefinition): array
    {
        // Exemple: enum('G','PG','PG-13','R','NC-17')
        if (preg_match("/^enum\((.+)\)$/i", $enumDefinition, $matches)) {
            $values = str_getcsv($matches[1], ',', "'");
            return array_map('trim', $values);
        }
        
        return [];
    }
    
    /**
     * Extrait les valeurs d'un type SET depuis sa définition.
     */
    public static function extractSetValues(string $setDefinition): array
    {
        // Exemple: set('Trailers','Commentaries','Deleted Scenes','Behind the Scenes')
        if (preg_match("/^set\((.+)\)$/i", $setDefinition, $matches)) {
            $values = str_getcsv($matches[1], ',', "'");
            return array_map('trim', $values);
        }
        
        return [];
    }
    
    /**
     * Détermine le type PHP approprié pour un type de colonne.
     */
    public static function mapToPhpType(string $columnType, bool $nullable = false): string
    {
        $baseType = strtolower(explode('(', $columnType)[0]);
        
        $typeMap = [
            'enum' => 'string',
            'set' => 'string',
            'year' => 'int',
            'bit' => 'bool',
            'tinyint' => 'int',
            'smallint' => 'int',
            'mediumint' => 'int',
            'int' => 'int',
            'integer' => 'int',
            'bigint' => 'int',
            'decimal' => 'string',
            'numeric' => 'string',
            'float' => 'float',
            'double' => 'float',
            'real' => 'float',
            'varchar' => 'string',
            'char' => 'string',
            'text' => 'string',
            'mediumtext' => 'string',
            'longtext' => 'string',
            'date' => '\DateTimeInterface',
            'datetime' => '\DateTimeInterface',
            'timestamp' => '\DateTimeInterface',
            'time' => '\DateTimeInterface',
            'json' => 'array',
            'blob' => 'string',
            'binary' => 'string',
            'varbinary' => 'string',
        ];
        
        $phpType = $typeMap[$baseType] ?? 'string';
        
        return $nullable ? "?{$phpType}" : $phpType;
    }

    /**
     * Génère des constantes de classe pour les valeurs ENUM.
     */
    public static function generateEnumConstants(array $enumValues, string $propertyName): array
    {
        $constants = [];
        $prefix = strtoupper($propertyName);
        
        foreach ($enumValues as $value) {
            $constantName = $prefix . '_' . strtoupper(preg_replace('/[^A-Z0-9]/', '_', $value));
            $constants[$constantName] = $value;
        }
        
        return $constants;
    }

    /**
     * Génère des constantes de classe pour les valeurs SET.
     */
    public static function generateSetConstants(array $setValues, string $propertyName): array
    {
        $constants = [];
        $prefix = strtoupper($propertyName);
        
        foreach ($setValues as $value) {
            $constantName = $prefix . '_' . strtoupper(preg_replace('/[^A-Z0-9]/', '_', $value));
            $constants[$constantName] = $value;
        }
        
        return $constants;
    }

    /**
     * Vérifie si un type de colonne est un type ENUM ou SET.
     */
    public static function isEnumOrSetType(string $columnType): bool
    {
        $lowerType = strtolower($columnType);
        return str_starts_with($lowerType, 'enum(') || str_starts_with($lowerType, 'set(');
    }
}