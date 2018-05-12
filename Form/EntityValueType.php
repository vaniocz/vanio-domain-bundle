<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityValueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ValueToEntityTransformer(
            $options['em'],
            $options['class'],
            (array) $options['property'],
            $options['multiple'],
            $options['query_builder']
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'documentation' => [],
                'documentation_type_mapping' => [
                    'text' => 'string',
                    'blob' => 'string',
                    'smallint' => 'integer',
                    'bigint' => 'integer',
                    'decimal' => 'float',
                    'datetime' => 'DateTime',
                    'datetimetz' => 'DateTime',
                    'time' => 'DateTime',
                    'date' => 'DateTime',
                    'json' => 'array',
                    'json_array' => 'array',
                    'simple_array' => 'array<string>',
                    'uuid' => [
                        'type' => 'uuid',
                        'example' => '7e57d004-2b97-0e7a-b45f-5387367791cd',
                    ],
                ],
            ])
            ->setRequired('property')
            ->setAllowedTypes('property', ['string', 'array'])
            ->setAllowedTypes('documentation_type_mapping', 'array')
            ->setNormalizer('documentation', $this->documentationNormalizer());
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    /**
     * @internal
     */
    public function documentationNormalizer(): \Closure
    {
        return function (Options $options, array $documentation) {
            /** @var EntityManager $entityManager */
            $entityManager = $options['em'];

            if (count($options['property']) === 1) {
                $type = $entityManager->getClassMetadata($options['class'])->getTypeOfField($options['property'][0]);

                if ($data = $options['documentation_type_mapping'][$type] ?? null) {
                    $documentation += is_array($data) ? $data : ['type' => $data];
                }
            }

            return $documentation;
        };
    }
}
