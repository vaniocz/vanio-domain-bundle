<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
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
        } catch (ValidationException $e) {
            $forms = iterator_to_array($forms);
            /** @var FormInterface|null $form */
            if (!$form = $forms ? reset($forms)->getParent() : null) {
                throw $e;
            }

            $message = $this->translator->trans($e->getMessageTemplate(), $e->getMessageParameters(), 'validation');
            $form->addError(new FormError($message, $e->getMessageTemplate(), $e->getMessageParameters()));
        }
    }
}
