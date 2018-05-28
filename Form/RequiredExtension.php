<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Vanio\Stdlib\Objects;

class RequiredExtension extends AbstractTypeExtension
{
    /** @var MetadataFactoryInterface */
    private $metadataFactory;

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
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
    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $groups = $this->resolveValidationGroups($form);

        if (!$form->getConfig() instanceof FormConfigBuilder || !$this->shouldValidateRequired($form, $groups)) {
            return;
        }

        $options = $form->getConfig()->getOptions();
        $options['constraints'][] = new NotBlank([
            'message' => $form->getConfig()->getOption('required_message'),
            'groups' => $groups,
        ]);
        Objects::setPropertyValue($form->getConfig(), 'options', $options, FormConfigBuilder::class);
    }

    /**
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

    private function shouldValidateRequired(FormInterface $form, array $groups): bool
    {
        if (!$form->isRequired() || !$this->resolveValidateRequired($form)) {
            return false;
        }

        $config = $form->getConfig();
        $parent = $form->getParent();
        $skippedParentTypes = ['choice', 'scalar_object', 'repeated'];

        if ($config->getCompound() && $config->getType()->getBlockPrefix() !== 'repeated') {
            return false;
        } elseif ($parent && in_array($parent->getConfig()->getType()->getBlockPrefix(), $skippedParentTypes)) {
            return false;
        } elseif ($this->hasNotBlankConstraint($config->getOption('constraints'), $groups)) {
            return false;
        }

        return !$parent || !$this->shouldValidateEmbedded($parent) || !$this->hasPropertyNotBlankConstraint(
            $parent->getConfig()->getDataClass(),
            $form->getPropertyPath(),
            $groups
        );
    }

    private function resolveValidateRequired(FormInterface $form): bool
    {
        do {
            $validateRequired = $form->getConfig()->getOption('validate_required');

            if ($validateRequired !== null) {
                return $validateRequired;
            }
        } while ($form = $form->getParent());

        return false;
    }

    private function hasNotBlankConstraint(array $constraints, array $groups): bool
    {
        foreach ($constraints as $constraint) {
            if ($constraint instanceof NotBlank && array_intersect($groups, $constraint->groups)) {
                return true;
            }
        }

        return false;
    }

    private function shouldValidateEmbedded(FormInterface $form): bool
    {
        $dataClass = $form->getConfig()->getDataClass();

        return $dataClass && (
            !$form->getParent()
            || $this->hasValidConstraint($form->getConfig()->getOption('constraints'))
            || $this->hasClassValidConstraint($dataClass)
        );
    }

    private function hasValidConstraint(array $constraints): bool
    {
        foreach ($constraints as $constraint) {
            if ($constraint instanceof Valid) {
                return true;
            }
        }

        return false;
    }

    private function hasClassValidConstraint(string $dataClass): bool
    {
        $metadata = $this->getMetadataFor($dataClass);

        return $metadata && $this->hasValidConstraint($metadata->getConstraints());
    }

    /**
     * @param string $dataClass
     * @param PropertyPath $propertyPath
     * @param string[] $groups
     * @return bool
     */
    private function hasPropertyNotBlankConstraint(string $dataClass, PropertyPath $propertyPath, array $groups): bool
    {
        if ($metadata = $this->getMetadataFor($dataClass)) {
            foreach ($propertyPath->getElements() as $element) {
                $metadata = $metadata->getPropertyMetadata($element);
            }

            foreach ($metadata as $propertyMetadata) {
                if ($this->hasNotBlankConstraint($propertyMetadata->getConstraints(), $groups)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return ClassMetadata|null
     */
    private function getMetadataFor(string $class)
    {
        return $this->metadataFactory->hasMetadataFor($class) ? $this->metadataFactory->getMetadataFor($class) : null;
    }
}
