<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\DomainBundle\Assert\LazyValidationException;
use Vanio\DomainBundle\Assert\ValidationException;

class ValidatingDataMapper implements DataMapperInterface
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
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);
        $parent = current($forms)->getParent();

        foreach ($validationErrors as $error) {
            $form = $parent;
            $propertyPath = $error->getPropertyPath();

            if ($propertyPath !== null) {
                foreach ($forms as $child) {
                    if ((string) $child->getPropertyPath() === $propertyPath) {
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
            $form->addError(new FormError($message, $error->getMessageTemplate(), $error->getMessageParameters()));
        }
    }
}
