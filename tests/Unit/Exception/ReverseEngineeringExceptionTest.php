<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\ReverseEngineeringException;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ReverseEngineeringException.
 */
class ReverseEngineeringExceptionTest extends TestCase
{
    public function testExceptionWithDefaultValues(): void
    {
        // Act
        $exception = new ReverseEngineeringException();

        // Assert
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomMessage(): void
    {
        // Arrange
        $message = 'Une erreur est survenue';

        // Act
        $exception = new ReverseEngineeringException($message);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomMessageAndCode(): void
    {
        // Arrange
        $message = 'Erreur avec code';
        $code = 500;

        // Act
        $exception = new ReverseEngineeringException($message, $code);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithPreviousException(): void
    {
        // Arrange
        $message = 'Erreur principale';
        $code = 400;
        $previous = new \Exception('Erreur précédente');

        // Act
        $exception = new ReverseEngineeringException($message, $code, $previous);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        // Act
        $exception = new ReverseEngineeringException();

        // Assert
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        // Assert
        $this->expectException(ReverseEngineeringException::class);
        $this->expectExceptionMessage('Test exception');
        $this->expectExceptionCode(123);

        // Act
        throw new ReverseEngineeringException('Test exception', 123);
    }

    public function testExceptionCanBeCaught(): void
    {
        // Arrange
        $message = 'Exception à capturer';
        $caught = false;

        // Act
        try {
            throw new ReverseEngineeringException($message);
        } catch (ReverseEngineeringException $e) {
            $caught = true;
            $this->assertEquals($message, $e->getMessage());
        }

        // Assert
        $this->assertTrue($caught);
    }
}