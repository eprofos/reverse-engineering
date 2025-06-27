<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;

/**
 * Service for mapping special MySQL types to supported Doctrine types.
 */
class MySQLTypeMapper
{
    /**
     * Registers custom MySQL types.
     */
    public static function registerCustomTypes(): void
    {
        // Register ENUM type as STRING
        if (! Type::hasType('enum')) {
            Type::addType('enum', StringType::class);
        }

        // Register SET type as STRING
        if (! Type::hasType('set')) {
            Type::addType('set', StringType::class);
        }
    }

    /**
     * Configures the platform to map MySQL types.
     */
    public static function configurePlatform(AbstractPlatform $platform): void
    {
        // Map ENUM to STRING
        $platform->registerDoctrineTypeMapping('enum', 'string');

        // Map SET to STRING
        $platform->registerDoctrineTypeMapping('set', 'string');

        // Other useful MySQL mappings
        $platform->registerDoctrineTypeMapping('year', 'integer');
        $platform->registerDoctrineTypeMapping('bit', 'boolean');
    }

    /**
     * Extracts values from an ENUM type definition.
     */
    public static function extractEnumValues(string $enumDefinition): array
    {
        // Example: enum('G','PG','PG-13','R','NC-17')
        if (preg_match('/^enum\\((.+)\\)$/i', $enumDefinition, $matches)) {
            $values = str_getcsv($matches[1], ',', "'");

            return array_map('trim', $values);
        }

        return [];
    }

    /**
     * Extracts values from a SET type definition.
     */
    public static function extractSetValues(string $setDefinition): array
    {
        // Example: set('Trailers','Commentaries','Deleted Scenes','Behind the Scenes')
        if (preg_match('/^set\\((.+)\\)$/i', $setDefinition, $matches)) {
            $values = str_getcsv($matches[1], ',', "'");

            return array_map('trim', $values);
        }

        return [];
    }

    /**
     * Determines the appropriate PHP type for a column type.
     */
    public static function mapToPhpType(string $columnType, bool $nullable = false): string
    {
        $baseType = strtolower(explode('(', $columnType)[0]);

        $typeMap = [
            'enum'       => 'string',
            'set'        => 'string',
            'year'       => 'int',
            'bit'        => 'bool',
            'tinyint'    => 'int',
            'smallint'   => 'int',
            'mediumint'  => 'int',
            'int'        => 'int',
            'integer'    => 'int',
            'bigint'     => 'int',
            'decimal'    => 'string',
            'numeric'    => 'string',
            'float'      => 'float',
            'double'     => 'float',
            'real'       => 'float',
            'varchar'    => 'string',
            'char'       => 'string',
            'text'       => 'string',
            'mediumtext' => 'string',
            'longtext'   => 'string',
            'date'       => '\DateTimeInterface',
            'datetime'   => '\DateTimeInterface',
            'timestamp'  => '\DateTimeInterface',
            'time'       => '\DateTimeInterface',
            'json'       => 'array',
            'blob'       => 'string',
            'binary'     => 'string',
            'varbinary'  => 'string',
        ];

        $phpType = $typeMap[$baseType] ?? 'string';

        return $nullable ? "?{$phpType}" : $phpType;
    }

    /**
     * Generates class constants for ENUM values.
     */
    public static function generateEnumConstants(array $enumValues, string $propertyName): array
    {
        $constants = [];
        $prefix    = self::normalizeConstantName($propertyName);

        foreach ($enumValues as $value) {
            $constantName             = $prefix . '_' . self::normalizeConstantName($value);
            $constants[$constantName] = $value;
        }

        return $constants;
    }

    /**
     * Generates class constants for SET values.
     */
    public static function generateSetConstants(array $setValues, string $propertyName): array
    {
        $constants = [];
        $prefix    = self::normalizeConstantName($propertyName);

        foreach ($setValues as $value) {
            $constantName             = $prefix . '_' . self::normalizeConstantName($value);
            $constants[$constantName] = $value;
        }

        return $constants;
    }

    /**
     * Normalizes a string to be used as a PHP constant name.
     * Converts to uppercase, handles spaces and special characters properly.
     */
    private static function normalizeConstantName(string $input): string
    {
        // Convert to uppercase first
        $normalized = strtoupper($input);
        
        // Replace sequences of non-alphanumeric characters with single underscores
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized);
        
        // Remove leading and trailing underscores
        $normalized = trim($normalized, '_');
        
        return $normalized;
    }

    /**
     * Checks if a column type is an ENUM or SET type.
     */
    public static function isEnumOrSetType(string $columnType): bool
    {
        $lowerType = strtolower($columnType);

        return str_starts_with($lowerType, 'enum(') || str_starts_with($lowerType, 'set(');
    }
}
