<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\DomainBundle\Assert\ValidationException;

class RequiredExtension extends AbstractTypeExtension
{
    /** @var ValidatorInterface */
    private $validator;

    /** @var FormInterface[] */
    private $forms = [];

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
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
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        if ($this->shouldValidateRequired($form)) {
            $this->forms[] = $form;
        }

        if (!$form->isRoot()) {
            return;
        }

        array_walk($this->forms, [$this, 'validateRequired']);
        $this->forms = [];
    }

    /**
     * @internal
     */
    public function validateRequired(FormInterface $form)
    {
        if (!$form->isSynchronized()) {
            return;
        }

        $groups = $this->resolveValidationGroups($form);

        if ($groups === [] || $groups === [false]) {
            return;
        }

        $target = $form;

        while ($target->getParent() && $target->getConfig()->getErrorBubbling()) {
            $target = $target->getParent();
        }

        if ($this->hasNotBlankOrNotNullError($target)) {
            return;
        }

        $constraint = new NotBlank(['message' => $form->getConfig()->getOption('required_message')]);

        foreach ($this->validator->validate($form->getData(), $constraint) as $violation) {
            $this->addViolationError($form, $violation);
        }
    }

    private function addViolationError(FormInterface $form, ConstraintViolationInterface $violation)
    {
        $form->addError(new FormError(
            $violation->getMessage(),
            $violation->getMessageTemplate(),
            $violation->getParameters(),
            $violation->getPlural(),
            $violation
        ));
    }

    private function hasNotBlankOrNotNullError(FormInterface $form): bool
    {
        foreach ($form->getErrors() as $error) {
            $cause = $error->getCause();

            if (
                $cause instanceof ValidationException
                && in_array($cause->getCode(), [Validation::INVALID_NOT_BLANK, Validation::VALUE_NULL])
            ) {
                return true;
            } elseif (!$cause instanceof ConstraintViolation) {
                continue;
            }

            $constraint = $cause->getConstraint();

            if ($constraint instanceof NotBlank || $constraint instanceof NotNull) {
                return true;
            }
        }

        return false;
    }

    private function shouldValidateRequired(FormInterface $form): bool
    {
        if (!$form->isRequired() || !$this->resolveValidateRequired($form)) {
            return false;
        }

        $config = $form->getConfig();
        $parent = $form->getParent();
        static $skippedParentTypes;

        if ($skippedParentTypes === null) {
            $skippedParentTypes = array_flip(['choice', 'repeated', 'scalar_object', 'entity_value']);
        }

        if ($config->getCompound() && $config->getType()->getBlockPrefix() !== 'repeated') {
            return false;
        } elseif ($parent && isset($skippedParentTypes[$parent->getConfig()->getType()->getBlockPrefix()])) {
            return false;
        }

        return true;
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

    /**
     * @return string[]
     */
    private function resolveValidationGroups(FormInterface $form): array
    {
        $resolveValidationGroups = function () use ($form) {
            return FormValidator::{'getValidationGroups'}($form);
        };
        $resolveValidationGroups = $resolveValidationGroups->bindTo(null, FormValidator::class);
        $validationGroups = $resolveValidationGroups();

        return $validationGroups instanceof GroupSequence
            ? $validationGroups->groups
            : $validationGroups;
    }
}
