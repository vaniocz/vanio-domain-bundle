<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vanio\DoctrineGenericTypes\Bundle\Form\ScalarObjectType;
use Vanio\Stdlib\Objects;

class RequiredExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'validate_required' => false,
                'required_message' => 'This value should not be blank.',
            ])
            ->setAllowedTypes('validate_required', 'bool')
            ->setAllowedTypes('required_message', 'string');
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();

        if (!$this->shouldValidateRequired($form) || !$form->getConfig() instanceof FormConfigBuilder) {
            return;
        }

        $options = $form->getConfig()->getOptions();
        $options['constraints'][] = new NotBlank([
            'message' => $form->getConfig()->getOption('required_message'),
            'groups' => $this->resolveValidationGroups($form),
        ]);
        Objects::setPropertyValue($form->getConfig(), 'options', $options, FormConfigBuilder::class);
    }

    private function shouldValidateRequired(FormInterface $form): bool
    {
        $parent = $form->getParent();

        if (
            !$form->isRequired()
            || $form->getConfig()->getCompound()
            || $parent && $parent->getConfig()->getType()->getInnerType() instanceof ScalarObjectType
        ) {
            return false;
        }

        foreach ($form->getConfig()->getOption('constraints') as $constraint) {
            if ($constraint instanceof NotBlank) {
                return false;
            }
        }

        while ($form = $form->getParent()) {
            if ($form->getConfig()->getOption('validate_required')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param FormInterface $form
     * @return string|string[]|null
     */
    private function resolveValidationGroups(FormInterface $form)
    {
        do {
            $validationGroups = $form->getConfig()->getOption('validation_groups');
            $form = $form->getParent();
        } while ($form && $validationGroups === null);

        return $validationGroups;
    }
}
