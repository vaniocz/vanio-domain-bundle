<?php
namespace Vanio\DomainBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class IdToEntityTransformer implements DataTransformerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var string */
    private $class;

    /** @var bool */
    private $isMultiple;

    public function __construct(EntityManagerInterface $entityManager, string $class, bool $isMultiple = false)
    {
        $this->entityManager = $entityManager;
        $this->class = $class;
        $this->isMultiple = $isMultiple;
    }

    /**
     * @param mixed $ids
     * @return object|object[]
     */
    public function transform($ids)
    {
        if ($ids === null) {
            return null;
        } elseif (!$this->isMultiple) {
            return $this->transformIdToEntity($ids);
        }

        $entities = [];

        foreach ($ids as $id) {
            $entities[] = $this->transformIdToEntity($id);
        }

        return $entities;
    }

    /**
     * @param object|object[] $entities
     * @return mixed
     */
    public function reverseTransform($entities)
    {
        if (!$this->isMultiple) {
            return $this->transformEntityToId($entities);
        }

        $ids = [];

        foreach ($entities as $entity) {
            $ids[] = $this->transformEntityToId($entity);
        }

        return $ids;
    }

    /**
     * @param mixed $id
     * @return object
     */
    public function transformIdToEntity($id)
    {
        $classMetadata = $this->entityManager->getClassMetadata($this->class);

        if (
            isset($classMetadata->identifierDiscriminatorField)
            && (!is_array($id) || !isset($id[$classMetadata->identifierDiscriminatorField]))
        ) {
            return $this->entityManager->getRepository($this->class)->find($id);
        }

        return $this->entityManager->getReference($this->class, $id);
    }

    /**
     * @param object $entity
     * @return mixed
     */
    public function transformEntityToId($entity)
    {
        $classMetadata = $this->entityManager->getClassMetadata($this->class);
        $id = $classMetadata->getIdentifierValues($entity);

        if (isset($classMetadata->identifierDiscriminatorField)) {
            unset($id[$classMetadata->identifierDiscriminatorField]);

            if (count($id) === 1) {
                return current($id);
            }
        }

        return $classMetadata->isIdentifierComposite ? $id : current($id);
    }
}
