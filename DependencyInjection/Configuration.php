<?php
namespace Vanio\DomainBundle\DependencyInjection;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder;
        $treeBuilder->root('vanio_domain')
            ->children()
                ->booleanNode('convert_get_post_parameters')->defaultTrue()->end()
                ->arrayNode('pagination_default_options')
                    ->children()
                        ->integerNode('records_per_page')->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
                ->arrayNode('translatable')
                    ->children()
                        ->scalarNode('enabled')->defaultTrue()->end()
                        ->scalarNode('translatable_fetch_mode')->defaultValue(ClassMetadata::FETCH_LAZY)->end()
                        ->scalarNode('translation_fetch_mode')->defaultValue(ClassMetadata::FETCH_LAZY)->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                    ->treatNullLike(['enabled' => false])
                    ->treatFalseLike(['enabled' => false])
                    ->treatTrueLike(['enabled' => true])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
