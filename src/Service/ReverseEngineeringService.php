<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ReverseEngineeringException;
use Exception;

use function count;

/**
 * Main service for reverse engineering orchestration.
 */
class ReverseEngineeringService
{
    public function __construct(
        private readonly DatabaseAnalyzer $databaseAnalyzer,
        private readonly MetadataExtractor $metadataExtractor,
        private readonly EntityGenerator $entityGenerator,
        private readonly FileWriter $fileWriter,
        private readonly array $config = [],
    ) {
        // Register custom MySQL types during initialization
        MySQLTypeMapper::registerCustomTypes();
    }

    /**
     * Generates entities from the database.
     *
     * @param array $options Generation options
     *
     * @throws ReverseEngineeringException
     *
     * @return array Generation result
     */
    public function generateEntities(array $options = []): array
    {
        try {
            // 1. Analyser la base de données
            $tables = $this->databaseAnalyzer->analyzeTables(
                $options['tables'] ?? [],
                $options['exclude'] ?? [],
            );

            if (empty($tables)) {
                throw new ReverseEngineeringException('No tables found to process.');
            }

            // 2. Extract metadata
            $metadata = [];

            foreach ($tables as $table) {
                $metadata[$table] = $this->metadataExtractor->extractTableMetadata($table, $tables);
            }

            // 3. Generate entities
            $entities = [];

            foreach ($metadata as $tableName => $tableMetadata) {
                $entities[] = $this->entityGenerator->generateEntity(
                    $tableName,
                    $tableMetadata,
                    $options,
                );
            }

            // 4. Write files (if not in dry-run mode)
            $files = [];

            if (! ($options['dry_run'] ?? false)) {
                foreach ($entities as $entity) {
                    $filePath = $this->fileWriter->writeEntityFile(
                        $entity,
                        $options['output_dir'] ?? null,
                        $options['force'] ?? false,
                    );
                    $files[] = $filePath;

                    // Write repository if present
                    if (isset($entity['repository'])) {
                        $repositoryPath = $this->fileWriter->writeRepositoryFile(
                            $entity['repository'],
                            $options['output_dir'] ?? null,
                            $options['force'] ?? false,
                        );
                        $files[] = $repositoryPath;
                    }
                }
            }

            return [
                'entities'         => $entities,
                'files'            => $files,
                'tables_processed' => count($tables),
            ];
        } catch (Exception $e) {
            throw new ReverseEngineeringException(
                'Error during entity generation: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Validates database configuration.
     *
     * @throws ReverseEngineeringException
     */
    public function validateDatabaseConnection(): bool
    {
        return $this->databaseAnalyzer->testConnection();
    }

    /**
     * Retrieves the list of available tables.
     *
     * @throws ReverseEngineeringException
     */
    public function getAvailableTables(): array
    {
        return $this->databaseAnalyzer->listTables();
    }

    /**
     * Retrieves information about a specific table.
     *
     * @throws ReverseEngineeringException
     */
    public function getTableInfo(string $tableName): array
    {
        return $this->metadataExtractor->extractTableMetadata($tableName);
    }
}
