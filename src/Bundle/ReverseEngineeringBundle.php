<?php

declare(strict_types=1);

namespace App\Bundle;

use App\DependencyInjection\ReverseEngineeringExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle principal pour l'ingénierie inverse de base de données.
 *
 * Ce bundle permet de générer automatiquement des entités Doctrine
 * à partir d'une base de données existante.
 */
class ReverseEngineeringBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    public function getContainerExtension(): ReverseEngineeringExtension
    {
        return new ReverseEngineeringExtension();
    }
}
