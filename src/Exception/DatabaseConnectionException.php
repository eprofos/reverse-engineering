<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception pour les erreurs de connexion à la base de données.
 */
class DatabaseConnectionException extends ReverseEngineeringException
{
    public function __construct(
        string $message = 'Erreur de connexion à la base de données',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
