<?php
namespace Vanio\DomainBundle\Pagination;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;

class OrderBy implements QueryModifier
{
    /** @var string[] */
    private $orderBy;

    /** @var string|bool|null */
    private $dqlAlias;

    /**
     * @param string|string[] $orderBy
     * @param string|bool|null $dqlAlias
     */
    public function __construct($orderBy, $dqlAlias = null)
    {
        $this->orderBy = is_array($orderBy) ? $orderBy : [$orderBy => 'ASC'];
        $this->dqlAlias = $dqlAlias;
    }

    /**
     * @param string $orderByString
     * @param string|bool|null $dqlAlias
     * @return self
     */
    public static function fromString(string $orderByString, $dqlAlias = null): self
    {
        $orderBy = [];

        foreach (preg_split('~,\s*~', $orderByString) as $order) {
            if (($order[0] ?? null) === '-') {
                $propertyPath = substr($order, 1);
                $direction = 'DESC';
            } else {
                list($propertyPath, $direction) = explode(' ', $order) + [null, 'ASC'];
            }

            $orderBy[$propertyPath] = $direction;
        }

        return new self($orderBy, $dqlAlias);
    }

    public function orderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias): void
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        if ($dqlAlias === false) {
            foreach ($this->orderBy as $orderBy => $direction) {
                $queryBuilder->addOrderBy($orderBy, $direction);
            }

            return;
        }

        $classMetadata = $this->getClassMetadata($queryBuilder, $dqlAlias);
        $entityManager = $queryBuilder->getEntityManager();

        foreach ($this->orderBy as $propertyPath => $direction) {
            $currentClassMetadata = $classMetadata;
            $propertyPath = explode('.', $propertyPath);
            $currentDqlAlias = $this->joinAssociations($queryBuilder, $dqlAlias, $currentClassMetadata, $propertyPath);
            $path = $this->resolveEmbeddedPath($entityManager, $currentDqlAlias, $currentClassMetadata, $propertyPath);
            $databasePlatform = $entityManager->getConnection()->getDatabasePlatform();

            if (count($propertyPath) > 1 && $this->isJsonField($currentClassMetadata, $databasePlatform, $propertyPath[0])) {
                $this->orderByJsonField($queryBuilder, $path, $propertyPath, $direction);
            } elseif (count($propertyPath) === 1 && $currentClassMetadata->hasField($propertyPath[0])) {
                $queryBuilder->addOrderBy(sprintf('%s.%s', $path, $propertyPath[0]), $direction);
            }
        }
    }

    private function joinAssociations(
        QueryBuilder $queryBuilder,
        string $alias,
        ClassMetadataInfo &$classMetadata,
        array &$propertyPath
    ): string {
        $count = count($propertyPath) - 1;

        for ($i = 0; $i < $count; $i++) {
            if (!$classMetadata->hasAssociation($propertyPath[0])) {
                break;
            }

            $property = array_shift($propertyPath);
            $relation = sprintf('%s.%s', $alias, $property);

            if ($join = $this->getJoin($queryBuilder, $relation)) {
                $alias = $join->getAlias();
            } else {
                $alias = sprintf('%s_%s', $alias, $property);

                if ($i === 0) {
                    $alias = sprintf('__%s', $alias);
                }

                $queryBuilder->leftJoin($relation, $alias);
            }

            $class = $classMetadata->getAssociationTargetClass($property);
            $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata($class);
        }

        return $alias;
    }

    private function resolveEmbeddedPath(
        EntityManager $entityManager,
        string $dqlAlias,
        ClassMetadataInfo &$classMetadata,
        array &$propertyPath
    ): string {
        $path = $dqlAlias;
        $count = count($propertyPath) - 1;

        for ($i = 0; $i < $count; $i++) {
            if ($embeddedClass = ($classMetadata->embeddedClasses[$propertyPath[0]]['class'] ?? null)) {
                $path = sprintf('%s.%s', $path, array_shift($propertyPath));
                $classMetadata = $entityManager->getClassMetadata($embeddedClass);
            }
        }

        return $path;
    }

    private function getClassMetadata(QueryBuilder $queryBuilder, string $alias): ClassMetadataInfo
    {
        /** @var From[] $froms */
        $froms = $queryBuilder->getDQLPart('from');

        foreach ($froms as $from) {
            if ($from->getAlias() === $alias) {
                return $queryBuilder->getEntityManager()->getClassMetadata($from->getFrom());
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'QueryBuilder does not contain FROM clause with "%s" alias.',
            $alias
        ));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $join
     * @return Join|null
     */
    private function getJoin(QueryBuilder $queryBuilder, string $join)
    {
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $dqlPart) {
                /** @var Join $dqlPart */
                if ($dqlPart->getJoin() === $join) {
                    return $dqlPart;
                }
            }
        }

        return null;
    }

    private function isJsonField(
        ClassMetadataInfo $classMetadata,
        AbstractPlatform $databasePlatform,
        string $field
    ): bool {
        if (!$classMetadata->hasField($field)) {
            return false;
        }

        $type = Type::getType($classMetadata->getTypeOfField($field));
        $fieldMapping = $classMetadata->getFieldMapping($field);

        if (!$databasePlatform->hasNativeJsonType()) {
            return false;
        }

        return in_array(
            $type->getSQLDeclaration($fieldMapping, $databasePlatform),
            [$databasePlatform->getJsonTypeDeclarationSQL($fieldMapping), 'jsonb']
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $path
     * @param string[] $propertyPath
     * @param string $direction
     */
    private function orderByJsonField(QueryBuilder $queryBuilder, string $path, array $propertyPath, string $direction)
    {
        $alias = sprintf('%s_%s', str_replace('.', '_', $path), implode('_', $propertyPath));
        $databasePlatform = $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform();
        $select = sprintf(
            'JSON_GET_PATH(%s.%s, %s) AS HIDDEN %s',
            $path,
            array_shift($propertyPath),
            implode(', ', array_map([$databasePlatform, 'quoteStringLiteral'], $propertyPath)),
            $alias
        );

        if (!$this->hasSelect($queryBuilder, $select)) {
            $queryBuilder->addSelect($select);
        }

        $queryBuilder->addOrderBy($alias, $direction);
    }

    private function hasSelect(QueryBuilder $queryBuilder, string $select): bool
    {
        /** @var Select[] $selects */
        $selects = $queryBuilder->getDQLPart('select');

        foreach ($selects as $s) {
            if (array_search($select, $s->getParts()) !== false) {
                return true;
            }
        }

        return false;
    }
}
