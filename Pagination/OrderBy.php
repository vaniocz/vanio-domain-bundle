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
    /** @var string */
    private $propertyPath;

    /** @var string */
    private $direction;

    /** @var string|null */
    private $dqlAlias;

    public function __construct(string $propertyPath, string $direction = 'ASC', string $dqlAlias = null)
    {
        $this->propertyPath = $propertyPath;
        $this->direction = $direction;
        $this->dqlAlias = $dqlAlias;
    }

    public function fromString(string $orderBy, string $dqlAlias = null): self
    {
        if (($orderBy[0] ?? null) === '-') {
            $propertyPath = substr($orderBy, 1);
            $direction = 'DESC';
        } else {
            list($propertyPath, $direction) = explode(' ', $orderBy) + [null, 'ASC'];
        }

        return new self($propertyPath, $direction, $dqlAlias);
    }

    public function propertyPath(): string
    {
        return $this->propertyPath;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        $classMetadata = $this->getClassMetadata($queryBuilder, $dqlAlias);
        $propertyPath = explode('.', $this->propertyPath);
        $dqlAlias = $this->joinAssociations($queryBuilder, $dqlAlias, $classMetadata, $propertyPath);
        $path = $this->resolveEmbeddedPath($queryBuilder->getEntityManager(), $dqlAlias, $classMetadata, $propertyPath);
        $databasePlatform = $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform();

        if (count($propertyPath) > 1 && $this->isFieldOfJsonType($classMetadata, $databasePlatform, $propertyPath[0])) {
            $this->orderByJsonField($queryBuilder, $path, $propertyPath);

            return;
        }

        $queryBuilder->addOrderBy(sprintf('%s.%s', $path, implode('.', $propertyPath)), $this->direction);
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

    /**
     * @throws \InvalidArgumentException
     */
    private function getClassMetadata(QueryBuilder $queryBuilder, string $alias): ClassMetadataInfo
    {
        /** @var From[] $froms */
        $froms = $queryBuilder->getDQLPart('from');

        foreach ($froms as $from) {
            if ($from->getAlias() === $alias) {
                return $queryBuilder->getEntityManager()->getClassMetadata($from->getFrom());
            }
        }

        throw new \InvalidArgumentException('QueryBuilder does not contain FROM clause with "%s" alias.', $alias);
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

    private function isFieldOfJsonType(
        ClassMetadataInfo $classMetadata,
        AbstractPlatform $databasePlatform,
        string $field
    ): bool {
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
     */
    private function orderByJsonField(QueryBuilder $queryBuilder, string $path, array $propertyPath)
    {
        $alias = sprintf('%s_%s', str_replace('.', '_', $path), implode('_', $propertyPath));
        $databasePlatform = $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform();
        $select = sprintf(
            'JSON_GET(%s.%s, %s) AS HIDDEN %s',
            $path,
            array_shift($propertyPath),
            implode(', ', array_map([$databasePlatform, 'quoteStringLiteral'], $propertyPath)),
            $alias
        );

        if (!$this->hasSelect($queryBuilder, $select)) {
            $queryBuilder->addSelect($select);
        }

        $queryBuilder->addOrderBy($alias, $this->direction);
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
