<?php
namespace Vanio\DomainBundle\Form;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class FormBuilderUtility
{
    public static function modifyOptions(FormBuilderInterface $builder, string $name, array $options)
    {
        $options += $builder->get($name)->getOptions();
        unset($options['choice_loader']);
        $builder->add($name, get_class($builder->get($name)->getType()->getInnerType()), $options);
    }
}
