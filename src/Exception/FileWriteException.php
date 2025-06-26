<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception for file writing errors.
 */
class FileWriteException extends ReverseEngineeringException
{
    public function __construct(
        string $message = 'File write error',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
