<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EntityIdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $idToEntityTransformer = new IdToEntityTransformer($options['em'], $options['class'], $options['multiple']);
        $builder->addModelTransformer($idToEntityTransformer);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
