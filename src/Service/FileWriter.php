<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle\Service;

use Eprofos\ReverseEngineeringBundle\Exception\FileWriteException;
use Exception;

use function sprintf;

/**
 * Service for writing generated entity files.
 */
class FileWriter
{
    public function __construct(
        private readonly string $projectDir,
        private readonly array $config = [],
    ) {
    }

    /**
     * Writes entity file to disk.
     *
     * @throws FileWriteException
     *
     * @return string The path of the created file
     */
    public function writeEntityFile(array $entity, ?string $outputDir = null, bool $force = false): string
    {
        try {
            $outputDir ??= $this->config['output_dir'] ?? 'src/Entity';

            // If path is absolute, use as is, otherwise combine with projectDir
            if (str_starts_with($outputDir, '/')) {
                $fullOutputDir = $outputDir;
            } else {
                $fullOutputDir = $this->projectDir . '/' . ltrim($outputDir, '/');
            }

            // Create directory if it doesn't exist
            $this->ensureDirectoryExists($fullOutputDir);

            $filePath = $fullOutputDir . '/' . $entity['filename'];

            // Check if file already exists
            if (file_exists($filePath) && ! $force) {
                throw new FileWriteException(
                    "File '{$entity['filename']}' already exists. Use --force option to overwrite.",
                );
            }

            // Write file
            $bytesWritten = file_put_contents($filePath, $entity['code']);

            if ($bytesWritten === false) {
                throw new FileWriteException(
                    "Failed to write file '{$filePath}'",
                );
            }

            return $filePath;
        } catch (Exception $e) {
            if ($e instanceof FileWriteException) {
                throw $e;
            }

            throw new FileWriteException(
                "Entity file write failed: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Writes repository file to disk.
     *
     * @throws FileWriteException
     *
     * @return string The path of the created file
     */
    public function writeRepositoryFile(array $repository, ?string $outputDir = null, bool $force = false): string
    {
        try {
            $outputDir ??= 'src/Repository';

            // If path is absolute, use as is, otherwise combine with projectDir
            if (str_starts_with($outputDir, '/')) {
                $fullOutputDir = $outputDir;
            } else {
                $fullOutputDir = $this->projectDir . '/' . ltrim($outputDir, '/');
            }

            // Create directory if it doesn't exist
            $this->ensureDirectoryExists($fullOutputDir);

            $filePath = $fullOutputDir . '/' . $repository['filename'];

            // Check if file already exists
            if (file_exists($filePath) && ! $force) {
                throw new FileWriteException(
                    "Repository file '{$repository['filename']}' already exists. Use --force option to overwrite.",
                );
            }

            // Use repository code if provided, otherwise generate it
            $repositoryCode = $repository['code'] ?? $this->generateRepositoryCode($repository);

            // Write file
            $bytesWritten = file_put_contents($filePath, $repositoryCode);

            if ($bytesWritten === false) {
                throw new FileWriteException(
                    "Failed to write repository file '{$filePath}'",
                );
            }

            return $filePath;
        } catch (Exception $e) {
            if ($e instanceof FileWriteException) {
                throw $e;
            }

            throw new FileWriteException(
                "Repository file write failed: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Validates that a directory can be used for writing.
     *
     * @throws FileWriteException
     */
    public function validateOutputDirectory(string $directory): bool
    {
        $fullPath = $this->projectDir . '/' . ltrim($directory, '/');

        if (file_exists($fullPath) && ! is_dir($fullPath)) {
            throw new FileWriteException(
                "Path '{$directory}' exists but is not a directory",
            );
        }

        if (file_exists($fullPath) && ! is_writable($fullPath)) {
            throw new FileWriteException(
                "Directory '{$directory}' is not writable",
            );
        }

        return true;
    }

    /**
     * Creates a directory if it doesn't exist.
     *
     * @throws FileWriteException
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (! file_exists($directory)) {
            if (! mkdir($directory, 0o755, true)) {
                throw new FileWriteException(
                    "Failed to create directory '{$directory}'",
                );
            }
        }

        if (! is_writable($directory)) {
            throw new FileWriteException(
                "Directory '{$directory}' is not writable",
            );
        }
    }

    /**
     * Generates repository code.
     */
    private function generateRepositoryCode(array $repository): string
    {
        $template = <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace %s;

            use %s;
            use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
            use Doctrine\Persistence\ManagerRegistry;

            /**
             * Repository for entity %s.
             *
             * @extends ServiceEntityRepository<%s>
             */
            class %s extends ServiceEntityRepository
            {
                public function __construct(ManagerRegistry $registry)
                {
                    parent::__construct($registry, %s::class);
                }

                /**
                 * Finds an entity by its ID.
                 *
                 * @param mixed $id
                 * @param int|null $lockMode
                 * @param int|null $lockVersion
                 * @return %s|null
                 */
                public function find($id, $lockMode = null, $lockVersion = null): ?%s
                {
                    return parent::find($id, $lockMode, $lockVersion);
                }

                /**
                 * Finds all entities.
                 *
                 * @return %s[]
                 */
                public function findAll(): array
                {
                    return parent::findAll();
                }

                /**
                 * Finds entities by criteria.
                 *
                 * @param array $criteria
                 * @param array|null $orderBy
                 * @param int|null $limit
                 * @param int|null $offset
                 * @return %s[]
                 */
                public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
                {
                    return parent::findBy($criteria, $orderBy, $limit, $offset);
                }

                /**
                 * Finds one entity by criteria.
                 *
                 * @param array $criteria
                 * @param array|null $orderBy
                 * @return %s|null
                 */
                public function findOneBy(array $criteria, ?array $orderBy = null): ?%s
                {
                    return parent::findOneBy($criteria, $orderBy);
                }
            }
            PHP;

        $entityName = basename(str_replace('\\', '/', $repository['entity_class']));

        return sprintf(
            $template,
            $repository['namespace'],           // repository namespace
            $repository['entity_class'],        // entity use statement
            $entityName,                        // entity name in comment
            $entityName,                        // entity name in @extends
            $repository['name'],                // repository class name
            $entityName,                        // entity name in constructor
            $entityName,                        // find() return type
            $entityName,                        // find() return type
            $entityName,                        // findAll() return type
            $entityName,                        // findBy() return type
            $entityName,                        // findOneBy() return type
            $entityName,                         // findOneBy() return type
        );
    }
}
