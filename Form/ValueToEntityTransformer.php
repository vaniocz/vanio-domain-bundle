<?php
namespace Vanio\DomainBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Form\DataTransformerInterface;

class ValueToEntityTransformer implements DataTransformerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ClassMetadata */
    private $classMetadata;

    /** @var string[] */
    private $properties;

    /** @var bool */
    private $isMultiple;

    /** @var bool */
    private $isPropertyIdentifier;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $class,
        $properties,
        bool $isMultiple = false
    ) {
        $this->entityManager = $entityManager;
        $this->classMetadata = $entityManager->getClassMetadata($class);
        $this->properties = (array) $properties;
        $this->isMultiple = $isMultiple;
        $this->isPropertyIdentifier = !array_diff($this->classMetadata->identifier, $this->properties);
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
        if (!array_diff($this->classMetadata->identifier, $this->properties)) {
            return $this->entityManager->getReference($this->classMetadata->name, $value);
        }

        $criteria = count($this->properties) > 1 ? $value : [current($this->properties) => $value];

        if (!$entity = $this->entityManager->getRepository($this->classMetadata->name)->findOneBy($criteria)) {
            throw EntityNotFoundException::fromClassNameAndIdentifier($this->classMetadata->name, $criteria);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @return mixed
     */
    public function transformEntityToValue($entity)
    {
        $values = [];
        
        foreach ($this->properties as $property) {
            $values[$property] = $this->classMetadata->getFieldValue($entity, $property);
        }

        return count($values) > 1 ? $values : current($values);
    }
}
