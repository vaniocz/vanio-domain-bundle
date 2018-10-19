<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Vanio\Stdlib\Objects;

class ConstructorPropertyPathMapper implements DataMapperInterface
{
    /** @var PropertyPathMapper */
    private $propertyPathMapper;

    /** @var string */
    private $factoryMethod;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor = null,
        string $factoryMethod = '__construct'
    ) {
        $this->propertyPathMapper = new PropertyPathMapper($propertyAccessor);
        $this->factoryMethod = $factoryMethod;
    }

    /**
     * @param mixed $data
     * @param \Traversable|FormInterface[]| $forms
     */
    public function mapDataToForms($data, $forms)
    {
        $this->propertyPathMapper->mapDataToForms($data, $forms);
    }

    /**
     * @param \Traversable|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $parameters = [];
        $data = null;

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();

            if ($propertyPath === null || !$form->isSubmitted() || !$form->isSynchronized() || $form->isDisabled()) {
                continue;
            }

            $parameters[(string) $propertyPath] = $form->getData();
        }

        if (isset($form)) {
            $data = Objects::create($form->getParent()->getConfig()->getDataClass(), $parameters, $this->factoryMethod);
        }
    }
}
