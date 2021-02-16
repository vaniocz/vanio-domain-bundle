<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener as BaseResizeFormListener;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['encode_entry_names']) {
            return;
        }

        $listeners = $builder->getEventDispatcher()->getListeners(FormEvents::PRE_SET_DATA);

        foreach ($listeners as $listener) {
            if ($listener[0] instanceof BaseResizeFormListener) {
                $builder->getEventDispatcher()->removeSubscriber($listener[0]);
                break;
            }
        }

        $resizeListener = new ResizeFormListener(
            $options['entry_type'],
            $options['entry_options'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty']
        );

        $builder->addEventSubscriber($resizeListener);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('encode_entry_names', false)
            ->setAllowedTypes('encode_entry_names', 'bool');
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$options['encode_entry_names']) {
            return;
        }

        foreach ($view->children as $child) {
            $child->vars['original_name'] = substr(rawurldecode(str_replace(':', '%', $child->vars['name'])), 1);
        }
    }

    public function getExtendedType(): string
    {
        return CollectionType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [CollectionType::class];
    }
}
