<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Happyr\DoctrineSpecification\Specification\Specification as SpecificationInterface;

abstract class Specification implements SpecificationInterface
{
    /** @var string|null */
    protected $dqlAlias;

    public function __construct(string $dqlAlias = null)
    {
        $this->dqlAlias = $dqlAlias;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $dqlAlias
     * @return string|null
     */
    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias)
    {
        $dqlAlias = $this->resolveAlias($dqlAlias);
        $specification = $this->buildSpecification($dqlAlias);

        return $specification instanceof Filter ? $specification->getFilter($queryBuilder, $dqlAlias) : null;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        $dqlAlias = $this->resolveAlias($dqlAlias);
        $specification = $this->buildSpecification($dqlAlias);

        if ($specification instanceof QueryModifier) {
            $specification->modify($queryBuilder, $dqlAlias);
        }
    }

    /**
     * @internal
     * @return mixed
     */
    abstract public function buildSpecification(string $dqlAlias);

    private function resolveAlias(string $dqlAlias): string
    {
        return $this->dqlAlias ?? $dqlAlias;
    }
}
