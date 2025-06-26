<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\FileWriteException;
use Exception;

use function sprintf;

/**
 * Service pour l'écriture des fichiers d'entités générées.
 */
class FileWriter
{
    public function __construct(
        private readonly string $projectDir,
        private readonly array $config = [],
    ) {
    }

    /**
     * Écrit le fichier d'entité sur le disque.
     *
     * @throws FileWriteException
     *
     * @return string Le chemin du fichier créé
     */
    public function writeEntityFile(array $entity, ?string $outputDir = null, bool $force = false): string
    {
        try {
            $outputDir ??= $this->config['output_dir'] ?? 'src/Entity';

            // Si le chemin est absolu, l'utiliser tel quel, sinon le combiner avec projectDir
            if (str_starts_with($outputDir, '/')) {
                $fullOutputDir = $outputDir;
            } else {
                $fullOutputDir = $this->projectDir . '/' . ltrim($outputDir, '/');
            }

            // Créer le répertoire s'il n'existe pas
            $this->ensureDirectoryExists($fullOutputDir);

            $filePath = $fullOutputDir . '/' . $entity['filename'];

            // Vérifier si le fichier existe déjà
            if (file_exists($filePath) && ! $force) {
                throw new FileWriteException(
                    "Le fichier '{$entity['filename']}' existe déjà. Utilisez l'option --force pour l'écraser.",
                );
            }

            // Écrire le fichier
            $bytesWritten = file_put_contents($filePath, $entity['code']);

            if ($bytesWritten === false) {
                throw new FileWriteException(
                    "Impossible d'écrire le fichier '{$filePath}'",
                );
            }

            return $filePath;
        } catch (Exception $e) {
            if ($e instanceof FileWriteException) {
                throw $e;
            }

            throw new FileWriteException(
                "Erreur lors de l'écriture du fichier d'entité : " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Écrit le fichier de repository sur le disque.
     *
     * @throws FileWriteException
     *
     * @return string Le chemin du fichier créé
     */
    public function writeRepositoryFile(array $repository, ?string $outputDir = null, bool $force = false): string
    {
        try {
            $outputDir ??= 'src/Repository';

            // Si le chemin est absolu, l'utiliser tel quel, sinon le combiner avec projectDir
            if (str_starts_with($outputDir, '/')) {
                $fullOutputDir = $outputDir;
            } else {
                $fullOutputDir = $this->projectDir . '/' . ltrim($outputDir, '/');
            }

            // Créer le répertoire s'il n'existe pas
            $this->ensureDirectoryExists($fullOutputDir);

            $filePath = $fullOutputDir . '/' . $repository['filename'];

            // Vérifier si le fichier existe déjà
            if (file_exists($filePath) && ! $force) {
                throw new FileWriteException(
                    "Le fichier repository '{$repository['filename']}' existe déjà. Utilisez l'option --force pour l'écraser.",
                );
            }

            // Utiliser le code du repository s'il est fourni, sinon le générer
            $repositoryCode = $repository['code'] ?? $this->generateRepositoryCode($repository);

            // Écrire le fichier
            $bytesWritten = file_put_contents($filePath, $repositoryCode);

            if ($bytesWritten === false) {
                throw new FileWriteException(
                    "Impossible d'écrire le fichier repository '{$filePath}'",
                );
            }

            return $filePath;
        } catch (Exception $e) {
            if ($e instanceof FileWriteException) {
                throw $e;
            }

            throw new FileWriteException(
                "Erreur lors de l'écriture du fichier repository : " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Valide qu'un répertoire peut être utilisé pour l'écriture.
     *
     * @throws FileWriteException
     */
    public function validateOutputDirectory(string $directory): bool
    {
        $fullPath = $this->projectDir . '/' . ltrim($directory, '/');

        if (file_exists($fullPath) && ! is_dir($fullPath)) {
            throw new FileWriteException(
                "Le chemin '{$directory}' existe mais n'est pas un répertoire",
            );
        }

        if (file_exists($fullPath) && ! is_writable($fullPath)) {
            throw new FileWriteException(
                "Le répertoire '{$directory}' n'est pas accessible en écriture",
            );
        }

        return true;
    }

    /**
     * Crée un répertoire s'il n'existe pas.
     *
     * @throws FileWriteException
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (! file_exists($directory)) {
            if (! mkdir($directory, 0o755, true)) {
                throw new FileWriteException(
                    "Impossible de créer le répertoire '{$directory}'",
                );
            }
        }

        if (! is_writable($directory)) {
            throw new FileWriteException(
                "Le répertoire '{$directory}' n'est pas accessible en écriture",
            );
        }
    }

    /**
     * Génère le code du repository.
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
             * Repository pour l'entité %s.
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
                 * Trouve une entité par son ID.
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
                 * Trouve toutes les entités.
                 *
                 * @return %s[]
                 */
                public function findAll(): array
                {
                    return parent::findAll();
                }

                /**
                 * Trouve des entités selon des critères.
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
                 * Trouve une entité selon des critères.
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
            $repository['namespace'],           // namespace du repository
            $repository['entity_class'],        // use de l'entité
            $entityName,                        // nom de l'entité dans le commentaire
            $entityName,                        // nom de l'entité dans @extends
            $repository['name'],                // nom de la classe repository
            $entityName,                        // nom de l'entité dans le constructeur
            $entityName,                        // type de retour find()
            $entityName,                        // type de retour find()
            $entityName,                        // type de retour findAll()
            $entityName,                        // type de retour findBy()
            $entityName,                        // type de retour findOneBy()
            $entityName,                         // type de retour findOneBy()
        );
    }
}
