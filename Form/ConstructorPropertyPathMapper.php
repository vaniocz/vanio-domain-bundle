<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ConstructorPropertyPathMapper implements DataMapperInterface
{
    /** @var PropertyPathMapper */
    private $propertyPathMapper;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyPathMapper = new PropertyPathMapper($propertyAccessor);
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

        if (!$form) {
            return;
        }

        $data = $this->createObject($form->getParent()->getConfig()->getDataClass(), $parameters);
    }

    /**
     * @param string $class
     * @param array $parameters
     * @return object
     * @throws \ReflectionException
     */
    private function createObject(string $class, array $parameters)
    {
        $reflectionClass = new \ReflectionClass($class);
        $arguments = [];

        foreach ($reflectionClass->getConstructor()->getParameters() as $reflectionParameter) {
            $name = $reflectionParameter->getName();
            $argument = null;

            if (array_key_exists($name, $parameters)) {
                $argument = $parameters[$name];
            } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                $argument = $reflectionParameter->getDefaultValue();
            }

            $reflectionType = $reflectionParameter->getType();

            if ($reflectionType && $reflectionType->isBuiltin()) {
                if ($argument !== null || !$reflectionType->allowsNull()) {
                    settype($argument, $reflectionType->getName());
                }
            }

            $arguments[] = $argument;
        }

        return $reflectionClass->newInstanceArgs($arguments);
    }
}
