<?php
namespace Vanio\DomainBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Vanio\DomainBundle\DependencyInjection\VanioDomainExtension;

class VanioDomainExtensionTest extends AbstractExtensionTestCase
{
    function test_default_configuration()
    {
        $this->load();
        $this->assertContainerBuilderHasParameter('vanio_domain', [
            'pagination_default_options' => [],
        ]);
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions(): array
    {
        return [new VanioDomainExtension];
    }
}
