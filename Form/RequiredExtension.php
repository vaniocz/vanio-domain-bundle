<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

        while ($form->getParent() && $form->getConfig()->getErrorBubbling()) {
            $form = $form->getParent();
        }

        if ($this->hasNotBlankOrNotNullViolationError($form)) {
            return;
        }

        foreach ($this->validator->validate($form->getData(), new NotBlank) as $violation) {
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

    private function hasNotBlankOrNotNullViolationError(FormInterface $form): bool
    {
        foreach ($form->getErrors() as $error) {
            $cause = $error->getCause();

            if (!$cause instanceof ConstraintViolation) {
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
            $skippedParentTypes = array_flip(['choice', 'scalar_object', 'repeated']);
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
}
