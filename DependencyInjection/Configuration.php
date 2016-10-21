<?php
namespace Vanio\DomainBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder;
        $treeBuilder->root('vanio_domain')
            ->children()
                ->arrayNode('pagination_default_options')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('records_per_page')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
