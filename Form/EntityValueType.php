<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityValueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ValueToEntityTransformer(
            $options['em'],
            $options['class'],
            (array) $options['property'],
            $options['multiple']
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('property')
            ->setAllowedTypes('property', ['string', 'array']);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
