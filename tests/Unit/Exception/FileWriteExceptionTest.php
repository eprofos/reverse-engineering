<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\FileWriteException;
use App\Exception\ReverseEngineeringException;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour FileWriteException.
 */
class FileWriteExceptionTest extends TestCase
{
    public function testExceptionWithDefaultMessage(): void
    {
        // Act
        $exception = new FileWriteException();

        // Assert
        $this->assertInstanceOf(ReverseEngineeringException::class, $exception);
        $this->assertEquals('Erreur lors de l\'écriture de fichier', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomMessage(): void
    {
        // Arrange
        $message = 'Impossible d\'écrire le fichier User.php';

        // Act
        $exception = new FileWriteException($message);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomMessageAndCode(): void
    {
        // Arrange
        $message = 'Permissions insuffisantes';
        $code = 403;

        // Act
        $exception = new FileWriteException($message, $code);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithPreviousException(): void
    {
        // Arrange
        $message = 'Erreur d\'écriture de fichier';
        $code = 500;
        $previous = new \RuntimeException('Disk full');

        // Act
        $exception = new FileWriteException($message, $code, $previous);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        // Act
        $exception = new FileWriteException();

        // Assert
        $this->assertInstanceOf(ReverseEngineeringException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        // Assert
        $this->expectException(FileWriteException::class);
        $this->expectExceptionMessage('Test file write error');

        // Act
        throw new FileWriteException('Test file write error');
    }

    public function testExceptionCanBeCaughtAsParentType(): void
    {
        // Arrange
        $message = 'File write failed';
        $caught = false;

        // Act
        try {
            throw new FileWriteException($message);
        } catch (ReverseEngineeringException $e) {
            $caught = true;
            $this->assertInstanceOf(FileWriteException::class, $e);
            $this->assertEquals($message, $e->getMessage());
        }

        // Assert
        $this->assertTrue($caught);
    }

    public function testExceptionWithFilePermissionScenario(): void
    {
        // Arrange
        $message = 'Le répertoire /path/to/entities n\'est pas accessible en écriture';
        $code = 403;

        // Act
        $exception = new FileWriteException($message, $code);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithDiskSpaceScenario(): void
    {
        // Arrange
        $message = 'Espace disque insuffisant pour écrire le fichier';
        $previous = new \RuntimeException('No space left on device');

        // Act
        $exception = new FileWriteException($message, 0, $previous);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}