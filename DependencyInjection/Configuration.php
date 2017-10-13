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
                ->booleanNode('convert_get_post_params')->defaultTrue()->end()
            ->end();

        return $treeBuilder;
    }
}
