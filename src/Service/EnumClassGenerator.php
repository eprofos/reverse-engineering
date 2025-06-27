<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle\Service;

use Eprofos\ReverseEngineeringBundle\Exception\EntityGenerationException;
use Exception;

use function preg_replace;
use function sprintf;
use function str_replace;
use function strtoupper;
use function ucfirst;
use function ucwords;

/**
 * Service for generating PHP 8.1 backed enum classes for MySQL ENUM columns.
 */
class EnumClassGenerator
{
    public function __construct(
        private readonly string $projectDir,
        private readonly array $config = [],
    ) {
    }

    /**
     * Generates enum class name from table and column names.
     *
     * @param string $tableName The database table name
     * @param string $columnName The database column name
     * @return string The generated enum class name (e.g., 'UserStatusEnum')
     */
    public function generateEnumClassName(string $tableName, string $columnName): string
    {
        // Convert table name to PascalCase (e.g., 'user_profiles' -> 'UserProfile')
        $tableNamePascal = $this->toPascalCase($tableName);
        
        // Convert column name to PascalCase (e.g., 'status_type' -> 'StatusType')
        $columnNamePascal = $this->toPascalCase($columnName);
        
        // Remove common suffixes from table name to avoid redundancy
        $tableNamePascal = preg_replace('/s$/', '', $tableNamePascal); // Remove trailing 's'
        
        return $tableNamePascal . $columnNamePascal . 'Enum';
    }

    /**
     * Generates enum class content with proper PHP 8.1 syntax.
     *
     * @param string $className The enum class name
     * @param array $enumValues Array of enum values from MySQL
     * @param string $tableName The database table name
     * @param string $columnName The database column name
     * @return string The complete enum class code
     * 
     * @throws EntityGenerationException
     */
    public function generateEnumContent(string $className, array $enumValues, string $tableName, string $columnName): string
    {
        try {
            $namespace = $this->config['enum_namespace'] ?? 'App\\Enum';
            
            $enumCases = [];
            foreach ($enumValues as $value) {
                $caseName = $this->generateEnumCaseName($value);
                $enumCases[] = sprintf("    case %s = '%s';", $caseName, addslashes($value));
            }
            
            $enumCasesString = implode("\n", $enumCases);
            
            $template = <<<'PHP'
<?php

declare(strict_types=1);

namespace %s;

/**
 * Enum for %s.%s values
 * Generated automatically by ReverseEngineeringBundle
 */
enum %s: string
{
%s
}
PHP;

            return sprintf(
                $template,
                $namespace,
                $tableName,
                $columnName,
                $className,
                $enumCasesString
            );
        } catch (Exception $e) {
            throw new EntityGenerationException(
                "Enum class generation failed for {$tableName}.{$columnName}: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Determines the enum file path in src/Enum/ directory.
     *
     * @param string $className The enum class name
     * @return string The full file path for the enum class
     */
    public function getEnumFilePath(string $className): string
    {
        $enumDir = $this->config['enum_output_dir'] ?? 'src/Enum';
        
        // If path is absolute, use as is, otherwise combine with projectDir
        if (str_starts_with($enumDir, '/')) {
            $fullEnumDir = $enumDir;
        } else {
            $fullEnumDir = $this->projectDir . '/' . ltrim($enumDir, '/');
        }
        
        return $fullEnumDir . '/' . $className . '.php';
    }

    /**
     * Generates a valid PHP enum case name from an enum value.
     *
     * @param string $enumValue The MySQL enum value
     * @return string Valid PHP enum case name
     */
    public function generateEnumCaseName(string $enumValue): string
    {
        // Convert to uppercase and replace non-alphanumeric characters with underscores
        $caseName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '_', $enumValue));
        
        // Remove consecutive underscores
        $caseName = preg_replace('/_+/', '_', $caseName);
        
        // Remove leading/trailing underscores
        $caseName = trim($caseName, '_');
        
        // Ensure it doesn't start with a number (prepend with underscore if needed)
        if (preg_match('/^[0-9]/', $caseName)) {
            $caseName = '_' . $caseName;
        }
        
        // Fallback for empty case names
        if (empty($caseName)) {
            $caseName = 'EMPTY_VALUE';
        }
        
        return $caseName;
    }

    /**
     * Writes enum file to disk.
     *
     * @param string $className The enum class name
     * @param string $enumContent The enum class content
     * @param bool $force Whether to overwrite existing files
     * @return string The path of the created file
     * 
     * @throws EntityGenerationException
     */
    public function writeEnumFile(string $className, string $enumContent, bool $force = false): string
    {
        try {
            $filePath = $this->getEnumFilePath($className);
            $directory = dirname($filePath);
            
            // Create directory if it doesn't exist
            $this->ensureDirectoryExists($directory);
            
            // Check if file already exists
            if (file_exists($filePath) && !$force) {
                throw new EntityGenerationException(
                    "Enum file '{$className}.php' already exists. Use --force option to overwrite."
                );
            }
            
            // Write file
            $bytesWritten = file_put_contents($filePath, $enumContent);
            
            if ($bytesWritten === false) {
                throw new EntityGenerationException(
                    "Failed to write enum file '{$filePath}'"
                );
            }
            
            return $filePath;
        } catch (Exception $e) {
            if ($e instanceof EntityGenerationException) {
                throw $e;
            }
            
            throw new EntityGenerationException(
                "Enum file write failed: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Generates the full namespace for an enum class.
     *
     * @param string $className The enum class name
     * @return string The full namespace with class name
     */
    public function getEnumFullyQualifiedName(string $className): string
    {
        $namespace = $this->config['enum_namespace'] ?? 'App\\Enum';
        
        return $namespace . '\\' . $className;
    }

    /**
     * Converts a string to PascalCase.
     *
     * @param string $string The input string
     * @return string The PascalCase string
     */
    private function toPascalCase(string $string): string
    {
        // Replace underscores and hyphens with spaces, then convert to title case
        $string = str_replace(['_', '-'], ' ', $string);
        $string = ucwords($string);
        
        // Remove spaces
        return str_replace(' ', '', $string);
    }

    /**
     * Creates a directory if it doesn't exist.
     *
     * @param string $directory The directory path
     * @throws EntityGenerationException
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new EntityGenerationException(
                    "Failed to create directory '{$directory}'"
                );
            }
        }
        
        if (!is_writable($directory)) {
            throw new EntityGenerationException(
                "Directory '{$directory}' is not writable"
            );
        }
    }
}