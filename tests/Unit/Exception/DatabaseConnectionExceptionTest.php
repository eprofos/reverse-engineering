<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\DatabaseConnectionException;
use App\Exception\ReverseEngineeringException;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour DatabaseConnectionException.
 */
class DatabaseConnectionExceptionTest extends TestCase
{
    public function testExceptionWithDefaultMessage(): void
    {
        // Act
        $exception = new DatabaseConnectionException();

        // Assert
        $this->assertInstanceOf(ReverseEngineeringException::class, $exception);
        $this->assertEquals('Erreur de connexion à la base de données', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomMessage(): void
    {
        // Arrange
        $message = 'Connexion impossible au serveur MySQL';

        // Act
        $exception = new DatabaseConnectionException($message);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomMessageAndCode(): void
    {
        // Arrange
        $message = 'Timeout de connexion';
        $code = 2002;

        // Act
        $exception = new DatabaseConnectionException($message, $code);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithPreviousException(): void
    {
        // Arrange
        $message = 'Erreur de connexion DBAL';
        $code = 500;
        $previous = new \PDOException('Connection refused');

        // Act
        $exception = new DatabaseConnectionException($message, $code, $previous);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        // Act
        $exception = new DatabaseConnectionException();

        // Assert
        $this->assertInstanceOf(ReverseEngineeringException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        // Assert
        $this->expectException(DatabaseConnectionException::class);
        $this->expectExceptionMessage('Test database error');

        // Act
        throw new DatabaseConnectionException('Test database error');
    }

    public function testExceptionCanBeCaughtAsParentType(): void
    {
        // Arrange
        $message = 'Database error';
        $caught = false;

        // Act
        try {
            throw new DatabaseConnectionException($message);
        } catch (ReverseEngineeringException $e) {
            $caught = true;
            $this->assertInstanceOf(DatabaseConnectionException::class, $e);
            $this->assertEquals($message, $e->getMessage());
        }

        // Assert
        $this->assertTrue($caught);
    }

    public function testExceptionWithEmptyMessage(): void
    {
        // Act
        $exception = new DatabaseConnectionException('');

        // Assert
        $this->assertEquals('', $exception->getMessage());
    }

    public function testExceptionWithNegativeCode(): void
    {
        // Arrange
        $code = -1;

        // Act
        $exception = new DatabaseConnectionException('Error', $code);

        // Assert
        $this->assertEquals($code, $exception->getCode());
    }
}