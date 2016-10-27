<?php
namespace Vanio\DomainBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vanio\DomainBundle\DependencyInjection\EmbeddedPropertyMetadataCompilerPass;

class VanioDomainBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new EmbeddedPropertyMetadataCompilerPass);
    }
}
