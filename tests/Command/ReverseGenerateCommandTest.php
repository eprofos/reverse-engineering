<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ReverseGenerateCommand;
use App\Exception\ReverseEngineeringException;
use App\Service\ReverseEngineeringService;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests unitaires pour ReverseGenerateCommand.
 */
class ReverseGenerateCommandTest extends TestCase
{
    private ReverseGenerateCommand $command;

    private ReverseEngineeringService|MockObject $service;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ReverseEngineeringService::class);
        $this->command = new ReverseGenerateCommand($this->service);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommandConfigurationIsCorrect(): void
    {
        // Assert
        $this->assertEquals('reverse:generate', $this->command->getName());
        $this->assertStringContainsString('Génère des entités Doctrine', $this->command->getDescription());

        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('tables'));
        $this->assertTrue($definition->hasOption('exclude'));
        $this->assertTrue($definition->hasOption('namespace'));
        $this->assertTrue($definition->hasOption('output-dir'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('dry-run'));
    }

    public function testExecuteSuccessWithDefaultOptions(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['users', 'posts']);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->with($this->callback(fn ($options) => $options['tables'] === []
                       && $options['exclude'] === []
                       && $options['namespace'] === null
                       && $options['output_dir'] === null
                       && $options['force'] === false
                       && $options['dry_run'] === false))
            ->willReturn([
                'entities' => [
                    ['name' => 'User', 'table' => 'users', 'namespace' => 'App\\Entity'],
                    ['name' => 'Post', 'table' => 'posts', 'namespace' => 'App\\Entity'],
                ],
                'files'            => ['/path/to/User.php', '/path/to/Post.php'],
                'tables_processed' => 2,
            ]);

        // Act
        $exitCode = $this->commandTester->execute([]);

        // Assert
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Reverse Engineering', $output);
        $this->assertStringContainsString('Connexion à la base de données validée', $output);
        $this->assertStringContainsString('2 table(s) trouvée(s)', $output);
        $this->assertStringContainsString('2 entité(s) générée(s)', $output);
        $this->assertStringContainsString('/path/to/User.php', $output);
        $this->assertStringContainsString('/path/to/Post.php', $output);
    }

    public function testExecuteWithSpecificTables(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['users', 'posts', 'comments']);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->with($this->callback(fn ($options) => $options['tables'] === ['users', 'posts']))
            ->willReturn([
                'entities' => [
                    ['name' => 'User', 'table' => 'users', 'namespace' => 'App\\Entity'],
                ],
                'files'            => ['/path/to/User.php'],
                'tables_processed' => 1,
            ]);

        // Act
        $exitCode = $this->commandTester->execute([
            '--tables' => ['users', 'posts'],
        ]);

        // Assert
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithExcludeTables(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['users', 'posts', 'temp_table']);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->with($this->callback(fn ($options) => $options['exclude'] === ['temp_table']))
            ->willReturn([
                'entities'         => [],
                'files'            => [],
                'tables_processed' => 0,
            ]);

        // Act
        $exitCode = $this->commandTester->execute([
            '--exclude' => ['temp_table'],
        ]);

        // Assert
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithDryRun(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['users']);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->with($this->callback(fn ($options) => $options['dry_run'] === true))
            ->willReturn([
                'entities' => [
                    ['name' => 'User', 'table' => 'users', 'namespace' => 'App\\Entity'],
                ],
                'files'            => [],
                'tables_processed' => 1,
            ]);

        // Act
        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        // Assert
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Aperçu des entités qui seraient générées', $output);
        $this->assertStringContainsString('Mode dry-run activé', $output);
        $this->assertStringContainsString('User (table: users, namespace: App\\Entity)', $output);
    }

    public function testExecuteWithCustomOptions(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['products']);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->with($this->callback(fn ($options) => $options['namespace'] === 'Custom\\Entity'
                       && $options['output_dir'] === 'custom/entities'
                       && $options['force'] === true))
            ->willReturn([
                'entities'         => [],
                'files'            => [],
                'tables_processed' => 0,
            ]);

        // Act
        $exitCode = $this->commandTester->execute([
            '--namespace'  => 'Custom\\Entity',
            '--output-dir' => 'custom/entities',
            '--force'      => true,
        ]);

        // Assert
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteFailsWhenDatabaseConnectionFails(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(false);

        $this->service
            ->expects($this->never())
            ->method('getAvailableTables');

        // Act
        $exitCode = $this->commandTester->execute([]);

        // Assert
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Impossible de se connecter à la base de données', $output);
    }

    public function testExecuteHandlesInvalidTables(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['users', 'posts']);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->willReturn([
                'entities'         => [],
                'files'            => [],
                'tables_processed' => 0,
            ]);

        // Act
        $exitCode = $this->commandTester->execute([
            '--tables' => ['users', 'invalid_table'],
        ]);

        // Assert
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Les tables suivantes n\'existent pas : invalid_table', $output);
    }

    public function testExecuteHandlesReverseEngineeringException(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['users']);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->willThrowException(new ReverseEngineeringException('Erreur de génération'));

        // Act
        $exitCode = $this->commandTester->execute([]);

        // Assert
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Erreur lors de la génération : Erreur de génération', $output);
    }

    public function testExecuteHandlesGenericException(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willThrowException(new Exception('Erreur inattendue'));

        // Act
        $exitCode = $this->commandTester->execute([]);

        // Assert
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Erreur lors de la génération : Erreur inattendue', $output);
    }

    public function testExecuteShowsVerboseErrorTrace(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['users']);

        $exception = new ReverseEngineeringException('Erreur détaillée');
        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->willThrowException($exception);

        // Act
        $exitCode = $this->commandTester->execute([], [
            'verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE,
        ]);

        // Assert
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Trace de l\'erreur', $output);
    }

    public function testExecuteWithMultipleInvalidTables(): void
    {
        // Arrange
        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn(['users']);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->willReturn([
                'entities'         => [],
                'files'            => [],
                'tables_processed' => 0,
            ]);

        // Act
        $exitCode = $this->commandTester->execute([
            '--tables' => ['invalid1', 'invalid2', 'users'],
        ]);

        // Assert
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Les tables suivantes n\'existent pas : invalid1, invalid2', $output);
    }

    public function testExecuteDisplaysCorrectTableCount(): void
    {
        // Arrange
        $availableTables = array_fill(0, 15, 'table');

        $this->service
            ->expects($this->once())
            ->method('validateDatabaseConnection')
            ->willReturn(true);

        $this->service
            ->expects($this->once())
            ->method('getAvailableTables')
            ->willReturn($availableTables);

        $this->service
            ->expects($this->once())
            ->method('generateEntities')
            ->willReturn([
                'entities'         => [],
                'files'            => [],
                'tables_processed' => 0,
            ]);

        // Act
        $exitCode = $this->commandTester->execute([]);

        // Assert
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('15 table(s) trouvée(s)', $output);
    }
}
