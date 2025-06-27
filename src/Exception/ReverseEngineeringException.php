<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle\Exception;

use Exception;
use Throwable;

/**
 * Base exception for the ReverseEngineering bundle.
 */
class ReverseEngineeringException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
