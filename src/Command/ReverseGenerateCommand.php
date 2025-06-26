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
 * Command to generate entities from an existing database.
 */
#[AsCommand(
    name: 'reverse:generate',
    description: 'Generates Doctrine entities from an existing database',
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
                'Specific tables to process (all if not specified)',
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Tables to exclude from processing',
            )
            ->addOption(
                'namespace',
                'ns',
                InputOption::VALUE_OPTIONAL,
                'Namespace for generated entities',
            )
            ->addOption(
                'output-dir',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output directory for entities',
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force overwriting of existing files',
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be generated without creating files',
            )
            ->setHelp(
                'This command analyzes an existing database and automatically ' .
                'generates the corresponding Doctrine entities.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔄 Reverse Engineering - Entity Generation');

        try {
            // 1. Validate database connection
            $io->section('🔍 Validating database connection...');

            if (! $this->reverseEngineeringService->validateDatabaseConnection()) {
                $io->error('❌ Unable to connect to database');

                return Command::FAILURE;
            }

            $io->success('✅ Database connection validated');

            // 2. List available tables
            $availableTables = $this->reverseEngineeringService->getAvailableTables();
            $io->text(sprintf('📊 %d table(s) found in database', count($availableTables)));

            // 3. Prepare options
            $options = [
                'tables'     => $input->getOption('tables'),
                'exclude'    => $input->getOption('exclude'),
                'namespace'  => $input->getOption('namespace'),
                'output_dir' => $input->getOption('output-dir'),
                'force'      => $input->getOption('force'),
                'dry_run'    => $input->getOption('dry-run'),
            ];

            // 4. Validate specified tables
            if (! empty($options['tables'])) {
                $invalidTables = array_diff($options['tables'], $availableTables);

                if (! empty($invalidTables)) {
                    $io->warning(sprintf(
                        'The following tables do not exist: %s',
                        implode(', ', $invalidTables),
                    ));
                }
            }

            // 5. Generate entities
            $io->section('⚙️ Generating entities...');
            $result = $this->reverseEngineeringService->generateEntities($options);

            // 6. Display results
            if ($options['dry_run']) {
                $io->section('📋 Preview of entities that would be generated:');

                foreach ($result['entities'] as $entity) {
                    $io->text(sprintf(
                        '- %s (table: %s, namespace: %s)',
                        $entity['name'],
                        $entity['table'],
                        $entity['namespace'],
                    ));
                }
                $io->note('Dry-run mode enabled: no files were created');
            } else {
                $io->success(sprintf(
                    '✅ %d entity(ies) generated successfully!',
                    count($result['entities']),
                ));

                $io->section('📁 Files created:');

                foreach ($result['files'] as $file) {
                    $io->text("- {$file}");
                }
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('❌ Error during generation: ' . $e->getMessage());

            if ($output->isVerbose()) {
                $io->section('🐛 Error trace:');
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
