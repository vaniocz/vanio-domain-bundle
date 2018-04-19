<?php
namespace Vanio\DomainBundle\Doctrine;

use Assert\Assertion;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException as DoctrineNonUniqueResultException;
use Doctrine\ORM\NoResultException as DoctrineNoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\ORM\UnitOfWork;
use Happyr\DoctrineSpecification\EntitySpecificationRepositoryInterface;
use Happyr\DoctrineSpecification\Exception\NonUniqueResultException;
use Happyr\DoctrineSpecification\Exception\NoResultException;
use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Logic\AndX;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Happyr\DoctrineSpecification\Result\ResultModifier;

/**
 * @method mixed findOneBy(array $criteria, array $orderBy = null)
 */
class EntityRepository extends BaseEntityRepository implements EntitySpecificationRepositoryInterface
{
    /** @var string */
    private $alias = 'e';

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

    public function findAll(array $orderBy = null): array
    {
        return $this->findBy([], $orderBy);
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
     */
    public function random($criteria = [], int $limit, int $lockMode = null): array
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

        return $hydrator->hydrateAll(
            $statement,
            $entityPersister->getResultSetMapping(),
            [UnitOfWork::HINT_DEFEREAGERLOAD => true]
        );
    }

    /**
     * @param array $criteria
     * @param int|null $lockMode
     * @return mixed
     */
    public function randomOne($criteria = [], int $lockMode = null)
    {
        $entities = $this->random($criteria, 1, $lockMode);

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

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param mixed $specification
     * @param ResultModifier $resultModifier
     * @return mixed
     */
    public function match($specification, ResultModifier $resultModifier = null)
    {
        $query = $this->getQuery($specification, $resultModifier, $resultTransformers);

        return $this->transformResult($query, $query->execute(), $resultTransformers);
    }

    /**
     * @param mixed $specification
     * @param ResultModifier $resultModifier
     * @return mixed
     */
    public function matchSingleResult($specification, ResultModifier $resultModifier = null)
    {
        $query = $this->getQuery($specification, $resultModifier, $resultTransformers);

        try {
            $result = $query->getSingleResult();
        } catch (DoctrineNonUniqueResultException $e) {
            throw new NonUniqueResultException($e->getMessage(), $e->getCode(), $e);
        } catch (DoctrineNoResultException $e) {
            throw new NoResultException($e->getMessage(), $e->getCode(), $e);
        }

        $transformedResult = $this->transformResult($query, [$result], $resultTransformers);

        if ($transformedResult !== null) {
            $result = $transformedResult;
            $count = $result instanceof \Iterator || $result instanceof \IteratorAggregate
                ? iterator_count($result)
                : count($result);

            if (!$count) {
                throw new NoResultException('Transformed result is empty.');
            } elseif ($count > 1) {
                throw new NonUniqueResultException('Transformed result is not unique.');
            }
        }

        return current($result);
    }

    /**
     * @param mixed $specification
     * @param ResultModifier $resultModifier
     * @return mixed
     */
    public function matchOneOrNullResult($specification, ResultModifier $resultModifier = null)
    {
        try {
            return $this->matchSingleResult($specification, $resultModifier);
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param AbstractQuery $query
     * @param mixed $result
     * @param ResultTransformer[] $resultTransformers
     * @return mixed
     */
    private function transformResult(AbstractQuery $query, $result, array $resultTransformers)
    {
        foreach ($resultTransformers as $resultTransformer) {
            $transformedResult = $resultTransformer->transformResult($query, $result);

            if ($transformedResult !== null) {
                $result = $transformedResult;
            }
        }

        return $result;
    }

    /**
     * @param mixed $specification
     * @return QueryBuilder
     */
    public function getQueryBuilder($specification): QueryBuilder
    {
        list($specification) = $this->mergeSpecifications($specification);
        $queryBuilder = $this->createQueryBuilder($this->getAlias());

        if ($specification instanceof QueryModifier) {
            $specification->modify($queryBuilder, $this->getAlias());
        }

        if ($specification instanceof Filter) {
            if ($filter = (string) $specification->getFilter($queryBuilder, $this->getAlias())) {
                $queryBuilder->andWhere($filter);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param mixed $specification
     * @param ResultModifier|null $resultModifier
     * @param ResultTransformer[] $resultTransformers
     * @return Query
     */
    public function getQuery($specification, ResultModifier $resultModifier = null, &$resultTransformers = []): Query
    {
        list($specification, $resultModifier, $resultTransformers) = $this->mergeSpecifications(
            $specification,
            $resultModifier
        );
        $queryBuilder = $this->createQueryBuilder($this->getAlias());

        if ($specification instanceof QueryModifier) {
            $specification->modify($queryBuilder, $this->getAlias());
        }

        if ($specification instanceof Filter) {
            if ($filter = (string) $specification->getFilter($queryBuilder, $this->getAlias())) {
                $queryBuilder->andWhere($filter);
            }
        }

        $query = $queryBuilder->getQuery();
        $resultModifier->modify($query);

        return $query;
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
        $resultTransformers = [];
        $resultModifier = new ResultModifierChain;

        if ($modifier) {
            $resultModifier->append($modifier);
        }

        foreach ($specifications as $specification) {
            if ($specification instanceof Filter || $specification instanceof QueryModifier) {
                $and->andX($specification);
                $resultModifier->append($specification);
            } elseif ($specification instanceof ResultModifier) {
                $resultModifier->append($specification);
            }

            if ($specification instanceof ResultTransformer) {
                $resultTransformers[] = $specification;
            }
        }

        if ($modifier instanceof ResultTransformer) {
            $resultTransformers[] = $modifier;
        }

        return [$and, $resultModifier, $resultTransformers];
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
