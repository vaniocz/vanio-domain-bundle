<?php
namespace Vanio\DomainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Vanio\DomainBundle\Mapping\EmbeddedPropertyMappingFactory;

class EmbeddedPropertyMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('vich_uploader.property_mapping_factory')) {
            $container
                ->getDefinition('vich_uploader.property_mapping_factory')
                ->setClass(EmbeddedPropertyMappingFactory::class)
                ->addMethodCall('setEntityManager', [new Reference('doctrine.orm.entity_manager')]);
        }
    }
}
