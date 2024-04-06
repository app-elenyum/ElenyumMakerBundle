<?php

namespace Elenyum\Maker\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('elenyum_maker');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('cache')
                    ->children()
                        ->booleanNode('enable')
                            ->info('define cache enable for get modules')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('item_id')
                            ->info('define cache item id')
                            ->defaultValue('elenyum_maker')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('root')
                    ->info('Root info for create files')
                    ->children()
                        ->scalarNode('path')
                            ->defaultValue('module')
                            ->info('target path for create module')
                            ->isRequired()
                        ->end()
                        ->scalarNode('namespace')
                            ->info('define cache item id')
                            ->defaultValue('Module')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
