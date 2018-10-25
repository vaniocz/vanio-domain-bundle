<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Vanio\DomainBundle\Assert\LazyValidationException;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\DomainBundle\Assert\ValidationException;
use Vanio\Stdlib\Objects;
use Vanio\Stdlib\Strings;

class ValidatingMapper implements DataMapperInterface
{
    /** @var DataMapperInterface */
    private $dataMapper;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(DataMapperInterface $dataMapper, TranslatorInterface $translator)
    {
        $this->dataMapper = $dataMapper;
        $this->translator = $translator;
    }

    /**
     * @param mixed $data
     * @param \Iterator|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        $this->dataMapper->mapDataToForms($data, $forms);
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        try {
            $this->dataMapper->mapFormsToData($forms, $data);
        } catch (LazyValidationException $e) {
            $this->addValidationErrors($forms, $e->getErrorExceptions());
            $data = null;
        } catch (ValidationException $e) {
            $this->addValidationErrors($forms, [$e]);
            $data = null;
        }
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param ValidationException[] $validationErrors
     */
    private function addValidationErrors($forms, array $validationErrors)
    {
        $forms = iterator_to_array($forms);
        $parent = current($forms)->getParent();

        foreach ($validationErrors as $error) {
            $form = $this->resolveTargetForm($parent, $this->normalizePropertyPath($error->getPropertyPath()));
            $message = $this->translator->trans(
                $error->getMessageTemplate(),
                $error->getMessageParameters(),
                'validators'
            );

            if (in_array($error->getCode(), [Validation::INVALID_NOT_BLANK, Validation::VALUE_NULL])) {
                $this->removeNotBlankAndNotNullConstraints($form);
            }

            $form->addError(new FormError(
                $message,
                $error->getMessageTemplate(),
                $error->getMessageParameters(),
                null,
                $error
            ));
        }
    }

    private function removeNotBlankAndNotNullConstraints(FormInterface $form)
    {
        $config = $form->getConfig();
        $options = $config->getOptions();
        $options['constraints'] = [];

        foreach ($config->getOption('constraints') as $constraint) {
            if (!$constraint instanceof NotBlank && !$constraint instanceof NotNull) {
                $options['constraints'][] = $constraint;
            }
        }

        Objects::setPropertyValue($config, 'options', $options, FormConfigBuilder::class);
    }

    private function resolveTargetForm(FormInterface $form, ?string $propertyPath)
    {
        if ($propertyPath === null) {
            return $form;
        }

        foreach ($form->all() as $child) {
            $childPropertyPath = $this->normalizePropertyPath($child->getPropertyPath());

            if ($propertyPath === $childPropertyPath) {
                return $child;
            } elseif (Strings::startsWith($propertyPath, "{$childPropertyPath}.")) {
                return $this->resolveTargetForm($child, substr($propertyPath, strlen($childPropertyPath) + 1));
            }
        }

        return $form;
    }

    private function normalizePropertyPath(?string $propertyPath): string
    {
        return trim(str_replace(['[', ']', '..'], ['.', '.', '.'], $propertyPath), '.');
    }
}
