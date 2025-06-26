<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception pour les erreurs de génération d'entités.
 */
class EntityGenerationException extends ReverseEngineeringException
{
    public function __construct(
        string $message = 'Erreur lors de la génération d\'entité',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
