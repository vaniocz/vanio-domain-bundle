<?php
namespace Vanio\DomainBundle;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Vanio\DomainBundle\DependencyInjection\OverrideDoctrineParamConverterPass;
use Vanio\DomainBundle\DependencyInjection\OverridePropertyMappingFactoryPass;

class VanioDomainBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new OverrideDoctrineParamConverterPass);
        $container->addCompilerPass(new OverridePropertyMappingFactoryPass);
    }

    public function boot(): void
    {
        if ($this->container->has('var_dumper.cloner')) {
            $cloner = $this->container->get('var_dumper.cloner');
            assert($cloner instanceof ClonerInterface);
            $cloner->addCasters([UuidInterface::class => function (UuidInterface $uuid) {
                return ['uuid' => (string) $uuid];
            }]);
        }
    }
}
