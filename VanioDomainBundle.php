<?php
namespace Vanio\DomainBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vanio\DomainBundle\DependencyInjection\OverrideDoctrineParamConverterPass;
use Vanio\DomainBundle\DependencyInjection\OverridePropertyMappingFactoryPass;

class VanioDomainBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new OverrideDoctrineParamConverterPass);
        $container->addCompilerPass(new OverridePropertyMappingFactoryPass);
    }
}
