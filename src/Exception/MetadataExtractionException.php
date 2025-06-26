<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception pour les erreurs d'extraction de métadonnées.
 */
class MetadataExtractionException extends ReverseEngineeringException
{
    public function __construct(
        string $message = 'Erreur lors de l\'extraction de métadonnées',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
