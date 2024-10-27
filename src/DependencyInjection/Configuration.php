<?php

namespace Elenyum\Maker\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Elenyum Maker Bundle
 * ==============================
 * * * * *
 *
 * This bundle provides a set of tools for generating Symfony modules.
 * It supports caching and configuration of Doctrine entities, as well as
 * custom paths and namespaces for generated modules.
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('elenyum_maker');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()

            // Cache Configuration
            ->arrayNode('cache')
            ->children()
            ->booleanNode('enable')
            ->info('Indicates whether caching is activated. Caching helps improve performance by storing retrieved modules for future use')
            ->defaultFalse()
        ->end()

        ->scalarNode('item_id')
        ->info('Sets the cache item identifier. Useful for managing cache data associated with module operations')
        ->defaultValue('elenyum_maker')
        ->end()
        ->end()
        ->end()

        // Root Configuration
        ->arrayNode('root')
        ->info('Root settings define where and how modules are created. These include the file path for module creation, the namespace used for autoloading, and a prefix for module identifiers.')
        ->children()
        ->scalarNode('path')
        ->info('Specifies the directory path where the module files will be created. Ensure that the path is either absolute or relative to the project root.')
        ->end()

        ->scalarNode('namespace')
        ->info('Defines the namespace for the created module. Important for organizing code and ensuring that classes are properly autoloaded.')
            ->defaultValue('Module')
        ->end()

        ->scalarNode('prefix')
        ->info('Specifies a prefix for module item IDs. Useful for avoiding conflicts when working with multiple modules.')
            ->defaultValue('Module')
        ->end()
        ->end()
        ->end()

        // config for documentation open api generation
        ->scalarNode('securityName')
            ->info('default security name for generation open api documentation')
            ->defaultValue('api_key')
            ->end()

        // Doctrine Configuration
        ->arrayNode('doctrine')
        ->info('Doctrine settings allow skipping specific entity names or paths to optimize performance by excluding unnecessary data.')
            ->useAttributeAsKey('key')
            ->example([
                'names' => [], // List of entity names to skip
                'paths' => []  // List of paths to exclude
            ])
            ->prototype('variable')->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
