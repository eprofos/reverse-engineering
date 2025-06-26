<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration du bundle ReverseEngineering.
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
                            ->info('Driver de base de données (pdo_mysql, pdo_pgsql, pdo_sqlite)')
                        ->end()
                        ->scalarNode('host')
                            ->defaultValue('localhost')
                            ->info('Hôte de la base de données')
                        ->end()
                        ->integerNode('port')
                            ->defaultNull()
                            ->info('Port de la base de données')
                        ->end()
                        ->scalarNode('dbname')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('Nom de la base de données')
                        ->end()
                        ->scalarNode('user')
                            ->defaultValue('root')
                            ->info('Utilisateur de la base de données')
                        ->end()
                        ->scalarNode('password')
                            ->defaultValue('')
                            ->info('Mot de passe de la base de données')
                        ->end()
                        ->scalarNode('charset')
                            ->defaultValue('utf8mb4')
                            ->info('Charset de la base de données')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('generation')
                    ->children()
                        ->scalarNode('namespace')
                            ->defaultValue('App\\Entity')
                            ->info('Namespace des entités générées')
                        ->end()
                        ->scalarNode('output_dir')
                            ->defaultValue('src/Entity')
                            ->info('Répertoire de sortie des entités')
                        ->end()
                        ->arrayNode('tables')
                            ->scalarPrototype()->end()
                            ->info('Liste des tables à traiter (toutes si vide)')
                        ->end()
                        ->arrayNode('exclude_tables')
                            ->scalarPrototype()->end()
                            ->info('Liste des tables à exclure')
                        ->end()
                        ->booleanNode('generate_repository')
                            ->defaultTrue()
                            ->info('Générer les classes Repository')
                        ->end()
                        ->booleanNode('use_annotations')
                            ->defaultFalse()
                            ->info('Utiliser les annotations au lieu des attributs PHP 8')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
