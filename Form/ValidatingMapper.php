<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vanio\DomainBundle\Assert\LazyValidationException;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\DomainBundle\Assert\ValidationException;
use Vanio\Stdlib\Objects;

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
        } catch (ValidationException $e) {
            $this->addValidationErrors($forms, [$e]);
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
        /** @var FormInterface[] $forms */

        foreach ($validationErrors as $error) {
            $form = $parent;
            $propertyPath = $error->getPropertyPath();

            if ($propertyPath !== null) {
                foreach ($forms as $child) {
                    if ($propertyPath === str_replace(['][', '[', ']'], ['.', '', ''], $child->getPropertyPath())) {
                        $form = $child;
                        break;
                    }
                }
            }

            if (!$form) {
                throw $error;
            }

            $message = $this->translator->trans(
                $error->getMessageTemplate(),
                $error->getMessageParameters(),
                'validators'
            );

            if ($error->getCode() === Validation::INVALID_NOT_BLANK) {
                $this->removeNotBlankConstraints($form);
            }

            $form->addError(new FormError($message, $error->getMessageTemplate(), $error->getMessageParameters()));
        }
    }

    private function removeNotBlankConstraints(FormInterface $form)
    {
        $config = $form->getConfig();
        $options = $config->getOptions();
        $options['constraints'] = [];

        foreach ($config->getOption('constraints') as $constraint) {
            if (!$constraint instanceof NotBlank) {
                $options['constraints'][] = $constraint;
            }
        }

        Objects::setPropertyValue($config, 'options', $options, FormConfigBuilder::class);
    }
}
