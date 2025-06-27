<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Extension for ReverseEngineering bundle configuration.
 */
class ReverseEngineeringExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config'),
        );

        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config        = $this->processConfiguration($configuration, $configs);

        // Define configuration parameters
        $container->setParameter('reverse_engineering.config', $config);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    public function getAlias(): string
    {
        return 'reverse_engineering';
    }
}
