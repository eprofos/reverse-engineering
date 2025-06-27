<?php

declare(strict_types=1);

namespace Eprofos\ReverseEngineeringBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for ReverseEngineering bundle.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('reverse_engineering');
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('database')
                    ->children()
                        ->scalarNode('driver')
                            ->defaultValue('pdo_mysql')
                            ->info('Database driver (pdo_mysql, pdo_pgsql, pdo_sqlite)')
                        ->end()
                        ->scalarNode('host')
                            ->defaultValue('localhost')
                            ->info('Database host')
                        ->end()
                        ->integerNode('port')
                            ->defaultNull()
                            ->info('Database port')
                        ->end()
                        ->scalarNode('dbname')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('Database name')
                        ->end()
                        ->scalarNode('user')
                            ->defaultValue('root')
                            ->info('Database user')
                        ->end()
                        ->scalarNode('password')
                            ->defaultValue('')
                            ->info('Database password')
                        ->end()
                        ->scalarNode('charset')
                            ->defaultValue('utf8mb4')
                            ->info('Database charset')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('generation')
                    ->children()
                        ->scalarNode('namespace')
                            ->defaultValue('App\\Entity')
                            ->info('Namespace for generated entities')
                        ->end()
                        ->scalarNode('output_dir')
                            ->defaultValue('src/Entity')
                            ->info('Output directory for entities')
                        ->end()
                        ->arrayNode('tables')
                            ->scalarPrototype()->end()
                            ->info('List of tables to process (all if empty)')
                        ->end()
                        ->arrayNode('exclude_tables')
                            ->scalarPrototype()->end()
                            ->info('List of tables to exclude')
                        ->end()
                        ->booleanNode('generate_repository')
                            ->defaultTrue()
                            ->info('Generate Repository classes')
                        ->end()
                        ->booleanNode('use_annotations')
                            ->defaultFalse()
                            ->info('Use annotations instead of PHP 8 attributes')
                        ->end()
                        ->scalarNode('enum_namespace')
                            ->defaultValue('App\\Enum')
                            ->info('Namespace for generated enum classes')
                        ->end()
                        ->scalarNode('enum_output_dir')
                            ->defaultValue('src/Enum')
                            ->info('Output directory for enum classes')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
