<?php
namespace Vanio\DomainBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Vanio\DomainBundle\Doctrine\ColumnHydrator;
use Vanio\DomainBundle\Doctrine\Functions\CastFunction;
use Vanio\DomainBundle\Doctrine\Functions\FieldFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonGetFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonGetPathFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonGetTextFunction;
use Vanio\DomainBundle\Doctrine\Functions\TopFunction;
use Vanio\DomainBundle\Doctrine\Functions\TsQueryFunction;
use Vanio\DomainBundle\Doctrine\Functions\TsRankFunction;
use Vanio\DomainBundle\Doctrine\Functions\UnaccentFunction;
use Vanio\DomainBundle\Doctrine\Types\TextArrayType;
use VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankCDFunction;

class VanioDomainExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(sprintf('%s/../Resources', __DIR__)));
        $loader->load('config.xml');
        $container->setParameter('vanio_domain', $config);

        foreach ($config as $key => $value) {
            $container->setParameter("vanio_domain.$key", $value);
        }

        foreach ($config['translatable'] as $key => $value) {
            $container->setParameter("vanio_domain.translatable.$key", $value);
        }

        if ($config['convert_get_post_parameters']) {
            $container
                ->getDefinition('vanio_domain.request.get_post_param_converter')
                ->setAbstract(false)
                ->addTag('request.param_converter', ['priority' => 1024, 'converter' => 'get_post']);
        }

        if ($config['translatable']['enabled']) {
            $container
                ->getDefinition('vanio_domain.translatable.translatable_listener')
                ->setAbstract(false)
                ->addTag('doctrine.event_subscriber', ['priority' => 100]);
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    'tsvector' => TsVector::class,
                    TextArrayType::NAME => TextArrayType::class,
                ],
            ],
            'orm' => [
                'hydrators' => [
                    'column' => ColumnHydrator::class,
                ],
                'dql' => [
                    'string_functions' => [
                        'UNACCENT' => UnaccentFunction::class,
                        'TSQUERY' => TsQueryFunction::class,
                        'TSRANK' => TsRankFunction::class,
                        'TSRANKCD' => TsRankCDFunction::class,
                        'TOP' => TopFunction::class,
                        'FIELD' => FieldFunction::class,
                        'CAST' => CastFunction::class,
                        'JSON_GET' => JsonGetFunction::class,
                        'JSON_GET_TEXT' => JsonGetTextFunction::class,
                        'JSON_GET_PATH' => JsonGetPathFunction::class,
                    ],
                ],
            ],
        ]);
    }
}
