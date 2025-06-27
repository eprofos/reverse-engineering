<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle\Exception;

use Throwable;

/**
 * Exception for metadata extraction errors.
 */
class MetadataExtractionException extends ReverseEngineeringException
{
    public function __construct(
        string $message = 'Metadata extraction failed',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
