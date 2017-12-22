<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
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
                'validate_required' => null,
                'required_message' => 'This value should not be blank.',
            ])
            ->setAllowedTypes('validate_required', ['bool', 'null'])
            ->setAllowedTypes('required_message', 'string');
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();

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
            || $parent && $parent->getConfig()->getType()->getInnerType() instanceof ChoiceType
            || $parent && $parent->getConfig()->getType()->getInnerType() instanceof ScalarObjectType
        ) {
            return false;
        }

        foreach ($form->getConfig()->getOption('constraints') as $constraint) {
            if ($constraint instanceof NotBlank) {
                return false;
            }
        }

        do {
            $validateRequired = $form->getConfig()->getOption('validate_required');

            if ($validateRequired !== null) {
                return $validateRequired;
            }
        } while ($form = $form->getParent());

        return false;
    }

    /**
     * @param FormInterface $form
     * @return string[]
     */
    private function resolveValidationGroups(FormInterface $form): array
    {
        $resolveValidationGroups = function () use ($form) {
            return FormValidator::{'getValidationGroups'}($form);
        };
        $resolveValidationGroups = $resolveValidationGroups->bindTo(null, FormValidator::class);

        return $resolveValidationGroups();
    }
}
