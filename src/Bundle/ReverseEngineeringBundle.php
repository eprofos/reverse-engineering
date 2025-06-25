<?php

declare(strict_types=1);

namespace App\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\ReverseEngineeringExtension;

/**
 * Bundle principal pour l'ingénierie inverse de base de données.
 * 
 * Ce bundle permet de générer automatiquement des entités Doctrine
 * à partir d'une base de données existante.
 */
class ReverseEngineeringBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ReverseEngineeringExtension
    {
        return new ReverseEngineeringExtension();
    }
}