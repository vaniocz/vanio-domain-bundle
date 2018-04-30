<?php
namespace Vanio\DomainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vanio\DomainBundle\Request\DoctrineParamConverter;

class OverrideDoctrineParamConverterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('sensio_framework_extra.converter.doctrine.orm')) {
            $container
                ->getDefinition('sensio_framework_extra.converter.doctrine.orm')
                ->setClass(DoctrineParamConverter::class);
        }
    }
}
