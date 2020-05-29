<?php
namespace Vanio\DomainBundle\DependencyInjection;

use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Vanio\DoctrineGenericTypes\DBAL\ScalarObjectType;
use Vanio\DoctrineGenericTypes\DBAL\UniversalJsonType;
use Vanio\DomainBundle\Doctrine\ColumnHydrator;
use Vanio\DomainBundle\Doctrine\Functions\ArrayFunction;
use Vanio\DomainBundle\Doctrine\Functions\ArrayPositionFunction;
use Vanio\DomainBundle\Doctrine\Functions\CastFunction;
use Vanio\DomainBundle\Doctrine\Functions\FieldFunction;
use Vanio\DomainBundle\Doctrine\Functions\AnyOfFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonbArrayElementsTextFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonbExistsAnyFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonBuildObjectFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonGetBooleanFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonGetNumberFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonGetObjectFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonGetPathFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonGetStringFunction;
use Vanio\DomainBundle\Doctrine\Functions\JsonObjectAggFunction;
use Vanio\DomainBundle\Doctrine\Functions\NullFunction;
use Vanio\DomainBundle\Doctrine\Functions\PadLeftFunction;
use Vanio\DomainBundle\Doctrine\Functions\PercentileContFunction;
use Vanio\DomainBundle\Doctrine\Functions\RegexpReplaceFunction;
use Vanio\DomainBundle\Doctrine\Functions\ReplaceFunction;
use Vanio\DomainBundle\Doctrine\Functions\TopFunction;
use Vanio\DomainBundle\Doctrine\Functions\UnaccentFunction;
use Vanio\DomainBundle\Doctrine\Types\JsonbType;
use Vanio\DomainBundle\Doctrine\Types\TextArrayType;
use Vanio\DomainBundle\Doctrine\Types\UuidArrayType;
use VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\PlainToTsQueryFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\PlainTsQueryFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\PlainTsRankFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\ToTsQueryFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsAndFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsOrFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsQueryFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankCDFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankFunction;

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

        if (isset($container->getParameter('kernel.bundles')['VichUploaderBundle'])) {
            $definition = $container->getDefinition('vanio_domain.cli.delete_unused_uploaded_files_command');
            $definition
                ->setAbstract(false)
                ->addTag('console.command', ['command' => 'vanio:delete-unused-uploaded-files']);
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    ScalarObjectType::NAME => ScalarObjectType::class,
                    UuidType::NAME => UuidType::class,
                    TsVector::NAME => TsVector::class,
                    TextArrayType::NAME => TextArrayType::class,
                    UuidArrayType::NAME => UuidArrayType::class,
                    JsonbType::NAME => JsonbType::class,
                    UniversalJsonType::NAME => UniversalJsonType::class,
                ],
                'mapping_types' => [
                    '_uuid' => 'text', // uuid[] - migrations
                ],
            ],
            'orm' => [
                'hydrators' => [
                    'column' => ColumnHydrator::class,
                ],
                'dql' => [
                    'numeric_functions' => [
                        'PERCENTILE_CONT' => PercentileContFunction::class,
                    ],
                    'string_functions' => [
                        'REPLACE' => ReplaceFunction::class,
                        'REGEXP_REPLACE' => RegexpReplaceFunction::class,
                        'UNACCENT' => UnaccentFunction::class,
                        'PAD_LEFT' => PadLeftFunction::class,
                        'TSQUERY' => TsQueryFunction::class,
                        'PLAIN_TSQUERY' => PlainTsQueryFunction::class,
                        'TSRANK' => TsRankFunction::class,
                        'PLAIN_TSRANK' => PlainTsRankFunction::class,
                        'TSRANKCD' => TsRankCDFunction::class,
                        'TO_TSQUERY' => ToTsQueryFunction::class,
                        'PLAINTO_TSQUERY' => PlainToTsQueryFunction::class,
                        'TS_AND' => TsAndFunction::class,
                        'TS_OR' => TsOrFunction::class,
                        'TOP' => TopFunction::class,
                        'FIELD' => FieldFunction::class,
                        'CAST' => CastFunction::class,
                        'ARRAY' => ArrayFunction::class,
                        'ARRAY_POSITION' => ArrayPositionFunction::class,
                        'NULL' => NullFunction::class,
                        'JSON_GET_OBJECT' => JsonGetObjectFunction::class,
                        'JSON_GET_STRING' => JsonGetStringFunction::class,
                        'JSON_GET_NUMBER' => JsonGetNumberFunction::class,
                        'JSON_GET_BOOLEAN' => JsonGetBooleanFunction::class,
                        'JSON_GET_PATH' => JsonGetPathFunction::class,
                        'JSON_BUILD_ARRAY' => JsonBuildObjectFunction::class,
                        'JSON_BUILD_OBJECT' => JsonBuildObjectFunction::class,
                        'JSON_OBJECT_AGG' => JsonObjectAggFunction::class,
                        'JSONB_ARRAY_ELEMENTS_TEXT' => JsonbArrayElementsTextFunction::class,
                        'ANY_OF' => AnyOfFunction::class,
                    ],
                    'boolean_functions' => [
                        'JSONB_EXISTS_ANY' => JsonbExistsAnyFunction::class,
                    ],
                ],
            ],
        ]);
    }
}
