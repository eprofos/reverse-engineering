<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception pour les erreurs d'écriture de fichiers.
 */
class FileWriteException extends ReverseEngineeringException
{
    public function __construct(
        string $message = 'Erreur lors de l\'écriture de fichier',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
