<?php
namespace Vanio\DomainBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Form\DataTransformerInterface;

class ValueToEntityTransformer implements DataTransformerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var string */
    private $class;

    /** @var string[] */
    private $properties;

    /** @var bool */
    private $isMultiple;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $class,
        $properties,
        bool $isMultiple = false
    ) {
        $this->entityManager = $entityManager;
        $this->class = $class;
        $this->properties = (array) $properties;
        $this->isMultiple = $isMultiple;
    }

    /**
     * @param mixed $values
     * @return object|object[]
     */
    public function transform($values)
    {
        if ($values === null) {
            return null;
        } elseif (!$this->isMultiple) {
            return $this->transformValueToEntity($values);
        }

        $entities = [];

        foreach ($values as $value) {
            $entities[] = $this->transformValueToEntity($value);
        }

        return $entities;
    }

    /**
     * @param object|object[]|null $entities
     * @return mixed
     */
    public function reverseTransform($entities)
    {
        if (!$this->isMultiple) {
            return $entities === null ? null : $this->transformEntityToValue($entities);
        }

        $values = [];

        foreach ($entities as $entity) {
            $values[] = $this->transformEntityToValue($entity);
        }

        return $values;
    }

    /**
     * @param mixed $value
     * @return object
     */
    public function transformValueToEntity($value)
    {
        $classMetadata = $this->entityManager->getClassMetadata($this->class);

        if (!array_diff($classMetadata->identifier, $this->properties)) {
            return $this->entityManager->getReference($this->class, $value);
        }
        
        $criteria = count($this->properties) > 1 ? $value : [current($this->properties) => $value];

        if (!$entity = $this->entityManager->getRepository($this->class)->findOneBy($criteria)) {
            throw EntityNotFoundException::fromClassNameAndIdentifier($this->class, $criteria);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @return mixed
     */
    public function transformEntityToValue($entity)
    {
        $classMetadata = $this->entityManager->getClassMetadata($this->class);
        $values = [];
        
        foreach ($this->properties as $property) {
            $values[$property] = $classMetadata->getFieldValue($entity, $property);
        }

        if (isset($classMetadata->identifierDiscriminatorField)) {
            unset($values[$classMetadata->identifierDiscriminatorField]);
        }

        return count($values) > 1 ? $values : current($values);
    }
}
