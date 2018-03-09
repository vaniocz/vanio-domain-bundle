<?php
namespace Vanio\DomainBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\DataTransformerInterface;

class ValueToEntityTransformer implements DataTransformerInterface
{
    /** @var EntityManager */
    private $entityManager;

    /** @var ClassMetadata */
    private $classMetadata;

    /** @var string[] */
    private $properties;

    /** @var bool */
    private $isMultiple;

    /** @var bool */
    private $isPropertyIdentifier;

    /** @var QueryBuilder|null */
    private $queryBuilder;

    public function __construct(
        EntityManager $entityManager,
        string $class,
        $properties,
        bool $isMultiple = false,
        QueryBuilder $queryBuilder = null
    ) {
        $this->entityManager = $entityManager;
        $this->classMetadata = $this->entityManager->getClassMetadata($class);
        $this->properties = (array) $properties;
        $this->isMultiple = $isMultiple;
        $this->isPropertyIdentifier = !array_diff($this->classMetadata->identifier, $this->properties);
        $this->queryBuilder = $queryBuilder;
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
        if ($this->isPropertyIdentifier) {
            return $this->entityManager->getReference($this->classMetadata->name, $value);
        }

        $criteria = count($this->properties) > 1 ? $value : [current($this->properties) => $value];

        if (!$entity = $this->findEntity($criteria)) {
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

    /**
     * @param array $criteria
     * @return mixed
     */
    private function findEntity(array $criteria)
    {
        if ($this->queryBuilder) {
            $queryBuilder = clone $this->queryBuilder;
            $alias = current($queryBuilder->getRootAliases());

            foreach ($criteria as $property => $v) {
                $field = sprintf('%s.%s', $alias, $property);

                if ($v === null) {
                    $queryBuilder->andWhere(sprintf('%s IS NULL', $field));
                } else {
                    $parameter = sprintf('__%s_%s', $alias, $property);
                    $queryBuilder
                        ->andWhere(sprintf('%s = :%s', $field, $parameter))
                        ->setParameter($parameter, $v);
                }
            }

            return $queryBuilder->getQuery()->getSingleResult();
        }

        return $this->entityManager->getRepository($this->classMetadata->name)->findOneBy($criteria);
    }
}
