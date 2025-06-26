<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception for entity generation errors.
 */
class EntityGenerationException extends ReverseEngineeringException
{
    public function __construct(
        string $message = 'Entity generation failed',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
