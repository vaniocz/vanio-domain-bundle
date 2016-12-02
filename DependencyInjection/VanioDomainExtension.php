<?php
namespace Vanio\DomainBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Vanio\DomainBundle\Doctrine\Functions\TsQueryFunction;
use Vanio\DomainBundle\Doctrine\Functions\TsRankFunction;
use VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankCDFunction;

class VanioDomainExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('config.xml');
        $container->setParameter('vanio_domain', $config);

        foreach ($config as $key => $value) {
            $container->setParameter("vanio_domain.$key", $value);
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        $doctrineConfig = [
            'dbal' => [
                'types' => [
                    'tsvector' => TsVector::class,
                ],
                'mapping_types' => [
                    'tsvector' => 'tsvector',
                ],
            ],
            'orm' => [
                'dql' => [
                    'string_functions' => [
                        'TSQUERY' => TsQueryFunction::class,
                        'TSRANK' => TsRankFunction::class,
                        'TSRANKCD' => TsRankCDFunction::class,
                    ],
                ],
            ],
        ];

        $container->prependExtensionConfig('doctrine', $doctrineConfig);
    }
}
