<?php
namespace Vanio\DomainBundle\Doctrine;

use Assert\Assertion;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\ORM\UnitOfWork;
use Happyr\DoctrineSpecification\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Logic\AndX;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Happyr\DoctrineSpecification\Result\ResultModifier;

/**
 * @method array match(Filter|QueryModifier|array $specification, ResultModifier $modifier = null)
 * @method mixed matchSingleResult(Filter|QueryModifier|array $specification, ResultModifier $modifier = null)
 * @method mixed matchOneOrNullResult(Filter|QueryModifier|array $specification, ResultModifier $modifier = null)
 */
class EntityRepository extends EntitySpecificationRepository
{
    /**
     * @param mixed $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @return mixed
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        $identifierDiscriminatorField = $this->_class->identifierDiscriminatorField ?? null;

        if ($identifierDiscriminatorField !== null && (!is_array($id) || !isset($id[$identifierDiscriminatorField]))) {
            return $this->loadEntity($this->normalizeId($id), $lockMode, $lockVersion);
        }

        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     * @param mixed $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @return mixed
     * @throws EntityNotFoundException
     */
    public function get($id = null, int $lockMode = null, int $lockVersion = null)
    {
        $entity = $id === null
            ? $this->loadEntity([], $lockMode, $lockVersion)
            : $this->find($id, $lockMode, $lockVersion);

        if (!$entity) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $this->_entityName,
                is_array($id) ? $id : [$id]
            );
        }

        return $entity;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return mixed
     * @throws EntityNotFoundException
     */
    public function getOneBy(array $criteria, array $orderBy = null)
    {
        if (!$entity = $this->findOneBy($criteria, $orderBy)) {
            throw EntityNotFoundException::fromClassNameAndIdentifier($this->_entityName, $criteria);
        }

        return $entity;
    }

    /**
     * @param mixed $id
     * @return bool
     */
    public function exists($id): bool
    {
        return $this->existsBy($this->normalizeId($id));
    }

    /**
     * @param Criteria|array $criteria
     * @return bool
     */
    public function existsBy($criteria): bool
    {
        $entityPersister = $this->_em->getUnitOfWork()->getEntityPersister($this->_class->name);
        $sql = str_replace('SELECT COUNT(*)', 'SELECT 1', $entityPersister->getCountSQL($criteria));
        list($parameters, $types) = $entityPersister->expandParameters($criteria);

        return (bool) $this->_em->getConnection()->executeQuery($sql, $parameters, $types)->fetchColumn();
    }

    /**
     * @param mixed $id
     * @return mixed
     */
    public function getReference($id)
    {
        return $this->_em->getReference($this->_entityName, $id);
    }


    /**
     * @param array|Criteria $criteria
     * @param int $limit
     * @param int|null $lockMode
     * @return mixed
     */
    public function random($criteria = [], int $limit = 1, int $lockMode = null)
    {
        $entityPersister = $this->_em->getUnitOfWork()->getEntityPersister($this->_class->name);
        $sql = $entityPersister->getSelectSQL($criteria, null, $lockMode, $limit);
        $limitSql = sprintf('LIMIT %d', $limit);
        $sql = substr_replace(
            $sql,
            sprintf('ORDER BY RANDOM() %s', $limitSql),
            strrpos($sql, $limitSql),
            strlen($limitSql)
        );

        list($parameters, $types) = $entityPersister->expandParameters($criteria);
        $statement = $this->_em->getConnection()->executeQuery($sql, $parameters, $types);
        $hydrator = $this->_em->newHydrator(Query::HYDRATE_OBJECT);
        $entities = $hydrator->hydrateAll(
            $statement,
            $entityPersister->getResultSetMapping(),
            [UnitOfWork::HINT_DEFEREAGERLOAD => true]
        );

        return $entities ? $entities[0] : null;
    }


    /**
     * @param object $entity
     */
    public function add($entity)
    {
        Assertion::isInstanceOf($entity, $this->_entityName, 'Cannot add an instance of "%s" to "%s" repository.');
        $this->assertNotChildEntity($entity, 'add');
        $this->_em->persist($entity);
    }

    /**
     * @param object $entity
     */
    public function remove($entity)
    {
        Assertion::isInstanceOf($entity, $this->_entityName, 'Cannot remove an instance of "%s" from "%s" repository.');
        $this->assertNotChildEntity($entity, 'remove');
        $this->_em->remove($entity);
    }

    /**
     * @param mixed $specifications
     * @param ResultModifier|null $modifier
     * @return Query
     */
    public function getQuery($specifications, ResultModifier $modifier = null): Query
    {
        list($specification, $modifier) = $this->mergeSpecifications($specifications, $modifier);

        return parent::getQuery($specification, $modifier);
    }

    /**
     * @param mixed $specifications
     * @param bool $fetchJoinCollection
     * @return Paginator
     */
    public function paginate($specifications, bool $fetchJoinCollection = true): Paginator
    {
        return new Paginator($this->getQuery($specifications), $fetchJoinCollection);
    }

    /**
     * @param array $criteria
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @return mixed
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    private function loadEntity(array $criteria, int $lockMode = null, int $lockVersion = null)
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);

        switch (true) {
            case $lockMode === LockMode::OPTIMISTIC:
                if (!$this->_class->isVersioned) {
                    throw OptimisticLockException::notVersioned($this->_entityName);
                }

                if ($entity = $persister->load($criteria)) {
                    $this->_em->lock($entity, $lockMode, $lockVersion);
                }

                return $entity;
            case $lockMode === LockMode::NONE:
            case $lockMode === LockMode::PESSIMISTIC_READ:
            case $lockMode === LockMode::PESSIMISTIC_WRITE:
                if (!$this->_em->getConnection()->isTransactionActive()) {
                    throw TransactionRequiredException::transactionRequired();
                }

                return $persister->load($criteria, null, null, [], $lockMode);
        }

        return $this->findOneBy($criteria);
    }

    /**
     * @param mixed $specifications
     * @param ResultModifier|null $modifier
     * @return array
     */
    private function mergeSpecifications($specifications, ResultModifier $modifier = null): array
    {
        $specifications = is_array($specifications) ? $specifications : [$specifications];
        $and = new AndX;

        foreach ($specifications as $specification) {
            if ($specification instanceof Filter || $specification instanceof QueryModifier) {
                $and->andX($specification);
            }

            if ($specification instanceof ResultModifier) {
                Assertion::null($modifier, 'Only one result modifier can be passed at once.');
                $modifier = $specification;
            }
        }

        return [$and, $modifier];
    }

    /**
     * @param mixed $id
     * @return array
     * @throws ORMException
     */
    public function normalizeId($id): array
    {
        $identifierDiscriminatorField = $this->_class->identifierDiscriminatorField ?? null;

        if (!is_array($id)) {
            try {
                $field = $this->_class->getSingleIdentifierFieldName();
            } catch (MappingException $e) {
                throw ORMInvalidArgumentException::invalidCompositeIdentifier();
            }

            $id = [$field => $id];
        }

        foreach ($id as &$value) {
            if (is_object($value) && $this->_em->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value))) {
                $value = $this->_em->getUnitOfWork()->getSingleIdentifierValue($value);

                if ($value === null) {
                    throw ORMInvalidArgumentException::invalidIdentifierBindingEntity();
                }
            }
        }

        $normalizedId = $id;

        foreach ($this->_class->identifier as $property) {
            if (isset($id[$property])) {
                $normalizedId[$property] = $id[$property];
                unset($id[$property]);
            } elseif ($property !== $identifierDiscriminatorField) {
                throw ORMException::missingIdentifierField($this->_class->name, $property);
            }
        }

        if ($id) {
            throw ORMException::unrecognizedIdentifierFields($this->_class->name, array_keys($id));
        }

        return $normalizedId;
    }

    /**
     * @param object $entity
     * @param string $action
     */
    private function assertNotChildEntity($entity, string $action)
    {
        $message = sprintf(
            'Cannot %s child entity "%s", write/modify operations through repository are forbidden.',
            $action,
            $this->_entityName
        );
        Assertion::false($entity instanceof ChildEntity, $message);
    }
}
