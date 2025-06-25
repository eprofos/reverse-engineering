<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\DatabaseAnalyzer;
use App\Service\MetadataExtractor;
use App\Service\EntityGenerator;
use App\Service\FileWriter;
use App\Exception\ReverseEngineeringException;

/**
 * Service principal pour l'orchestration du reverse engineering.
 */
class ReverseEngineeringService
{
    public function __construct(
        private readonly DatabaseAnalyzer $databaseAnalyzer,
        private readonly MetadataExtractor $metadataExtractor,
        private readonly EntityGenerator $entityGenerator,
        private readonly FileWriter $fileWriter,
        private readonly array $config = []
    ) {
    }

    /**
     * Génère les entités à partir de la base de données.
     *
     * @param array $options Options de génération
     * @return array Résultat de la génération
     * @throws ReverseEngineeringException
     */
    public function generateEntities(array $options = []): array
    {
        try {
            // 1. Analyser la base de données
            $tables = $this->databaseAnalyzer->analyzeTables(
                $options['tables'] ?? [],
                $options['exclude'] ?? []
            );

            if (empty($tables)) {
                throw new ReverseEngineeringException('Aucune table trouvée à traiter.');
            }

            // 2. Extraire les métadonnées
            $metadata = [];
            foreach ($tables as $table) {
                $metadata[$table] = $this->metadataExtractor->extractTableMetadata($table, $tables);
            }

            // 3. Générer les entités
            $entities = [];
            foreach ($metadata as $tableName => $tableMetadata) {
                $entities[] = $this->entityGenerator->generateEntity(
                    $tableName,
                    $tableMetadata,
                    $options
                );
            }

            // 4. Écrire les fichiers (si pas en mode dry-run)
            $files = [];
            if (!($options['dry_run'] ?? false)) {
                foreach ($entities as $entity) {
                    $filePath = $this->fileWriter->writeEntityFile(
                        $entity,
                        $options['output_dir'] ?? null,
                        $options['force'] ?? false
                    );
                    $files[] = $filePath;
                    
                    // Écrire le repository si présent
                    if (isset($entity['repository'])) {
                        $repositoryPath = $this->fileWriter->writeRepositoryFile(
                            $entity['repository'],
                            null,
                            $options['force'] ?? false
                        );
                        $files[] = $repositoryPath;
                    }
                }
            }

            return [
                'entities' => $entities,
                'files' => $files,
                'tables_processed' => count($tables),
            ];

        } catch (\Exception $e) {
            throw new ReverseEngineeringException(
                'Erreur lors de la génération des entités : ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Valide la configuration de la base de données.
     *
     * @return bool
     * @throws ReverseEngineeringException
     */
    public function validateDatabaseConnection(): bool
    {
        return $this->databaseAnalyzer->testConnection();
    }

    /**
     * Récupère la liste des tables disponibles.
     *
     * @return array
     * @throws ReverseEngineeringException
     */
    public function getAvailableTables(): array
    {
        return $this->databaseAnalyzer->listTables();
    }

    /**
     * Récupère les informations sur une table spécifique.
     *
     * @param string $tableName
     * @return array
     * @throws ReverseEngineeringException
     */
    public function getTableInfo(string $tableName): array
    {
        return $this->metadataExtractor->extractTableMetadata($tableName);
    }
}