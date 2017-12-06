<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\DomainBundle\Assert\ValidationException;
use Vanio\Stdlib\Objects;
use Vanio\Stdlib\Strings;

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
        } catch (ValidationException $e) {
            $data = null;
            $forms = iterator_to_array($forms);

            /** @var FormInterface|null $form */
            if (!$form = $forms ? reset($forms)->getParent() : null) {
                throw $e;
            }

            $message = $this->translator->trans($e->getMessageTemplate(), $e->getMessageParameters(), 'validators');

            if ($e->getPropertyPath() !== null) {
                $form = PropertyAccess::createPropertyAccessor()->getValue(
                    $form,
                    $this->normalizePropertyPath($e->getPropertyPath())
                );
            }

            if ($e->getCode() === Validation::INVALID_NOT_BLANK) {
                $this->removeNotBlankConstraints($form);
            }

            $form->addError(new FormError($message, $e->getMessageTemplate(), $e->getMessageParameters()));
        }
    }

    private function normalizePropertyPath(string $propertyPath): string
    {
        return Strings::startsWith($propertyPath, '[')
            ? $propertyPath
            : sprintf('[%s]', str_replace('.', '][', $propertyPath));
    }

    private function removeNotBlankConstraints(FormInterface $form): void
    {
        $formConfig = $form->getConfig();
        $options = $formConfig->getOptions();
        $options['constraints'] = [];

        foreach ($formConfig->getOption('constraints') as $constraint) {
            if (!$constraint instanceof NotBlank) {
                $options['constraints'][] = $constraint;
            }
        }

        Objects::setPropertyValue($formConfig, 'options', $options, FormConfigBuilder::class);
    }
}
