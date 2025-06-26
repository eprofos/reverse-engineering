<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ReverseEngineeringService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function sprintf;

/**
 * Commande pour générer des entités à partir d'une base de données existante.
 */
#[AsCommand(
    name: 'reverse:generate',
    description: 'Génère des entités Doctrine à partir d\'une base de données existante',
)]
class ReverseGenerateCommand extends Command
{
    public function __construct(
        private readonly ReverseEngineeringService $reverseEngineeringService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'tables',
                't',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Tables spécifiques à traiter (toutes si non spécifié)',
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Tables à exclure du traitement',
            )
            ->addOption(
                'namespace',
                'ns',
                InputOption::VALUE_OPTIONAL,
                'Namespace des entités générées',
            )
            ->addOption(
                'output-dir',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Répertoire de sortie des entités',
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forcer l\'écrasement des fichiers existants',
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Afficher ce qui serait généré sans créer les fichiers',
            )
            ->setHelp(
                'Cette commande analyse une base de données existante et génère ' .
                'automatiquement les entités Doctrine correspondantes.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔄 Reverse Engineering - Génération d\'entités');

        try {
            // 1. Valider la connexion à la base de données
            $io->section('🔍 Validation de la connexion à la base de données...');

            if (! $this->reverseEngineeringService->validateDatabaseConnection()) {
                $io->error('❌ Impossible de se connecter à la base de données');

                return Command::FAILURE;
            }

            $io->success('✅ Connexion à la base de données validée');

            // 2. Lister les tables disponibles
            $availableTables = $this->reverseEngineeringService->getAvailableTables();
            $io->text(sprintf('📊 %d table(s) trouvée(s) dans la base de données', count($availableTables)));

            // 3. Préparer les options
            $options = [
                'tables'     => $input->getOption('tables'),
                'exclude'    => $input->getOption('exclude'),
                'namespace'  => $input->getOption('namespace'),
                'output_dir' => $input->getOption('output-dir'),
                'force'      => $input->getOption('force'),
                'dry_run'    => $input->getOption('dry-run'),
            ];

            // 4. Valider les tables spécifiées
            if (! empty($options['tables'])) {
                $invalidTables = array_diff($options['tables'], $availableTables);

                if (! empty($invalidTables)) {
                    $io->warning(sprintf(
                        'Les tables suivantes n\'existent pas : %s',
                        implode(', ', $invalidTables),
                    ));
                }
            }

            // 5. Générer les entités
            $io->section('⚙️ Génération des entités...');
            $result = $this->reverseEngineeringService->generateEntities($options);

            // 6. Afficher les résultats
            if ($options['dry_run']) {
                $io->section('📋 Aperçu des entités qui seraient générées :');

                foreach ($result['entities'] as $entity) {
                    $io->text(sprintf(
                        '- %s (table: %s, namespace: %s)',
                        $entity['name'],
                        $entity['table'],
                        $entity['namespace'],
                    ));
                }
                $io->note('Mode dry-run activé : aucun fichier n\'a été créé');
            } else {
                $io->success(sprintf(
                    '✅ %d entité(s) générée(s) avec succès !',
                    count($result['entities']),
                ));

                $io->section('📁 Fichiers créés :');

                foreach ($result['files'] as $file) {
                    $io->text("- {$file}");
                }
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('❌ Erreur lors de la génération : ' . $e->getMessage());

            if ($output->isVerbose()) {
                $io->section('🐛 Trace de l\'erreur :');
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
