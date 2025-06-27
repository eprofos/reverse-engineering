<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle;

use Eprofos\ReverseEngineeringBundle\DependencyInjection\ReverseEngineeringExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Main bundle for database reverse engineering.
 *
 * This bundle allows automatic generation of Doctrine entities
 * from an existing database.
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