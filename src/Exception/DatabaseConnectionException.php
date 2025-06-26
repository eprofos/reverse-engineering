<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception for database connection errors.
 */
class DatabaseConnectionException extends ReverseEngineeringException
{
    public function __construct(
        string $message = 'Database connection failed',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
