<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Query\QueryModifier;

class Join implements QueryModifier
{
    /** @var string */
    private $join;

    /** @var string */
    private $joinDqlAlias;

    /** @var string|bool|null */
    private $dqlAlias;

    /** @var string|null */
    private $condition;

    /** @var string */
    private $joinMethod;

    private function __construct()
    {}

    /**
     * @param string $join
     * @param string $joinDqlAlias
     * @param string|bool|null $dqlAlias
     * @param string|null $condition
     * @return $this
     */
    public static function inner(string $join, string $joinDqlAlias, $dqlAlias = null, string $condition = null): self
    {
        $self = new self;
        $self->join = $join;
        $self->joinDqlAlias = $joinDqlAlias;
        $self->dqlAlias = $dqlAlias;
        $self->condition = $condition;
        $self->joinMethod = 'innerJoin';

        return $self;
    }

    /**
     * @param string $join
     * @param string $joinDqlAlias
     * @param string|bool|null $dqlAlias
     * @param string|null $condition
     * @return $this
     */
    public static function left(string $join, string $joinDqlAlias, $dqlAlias = null, string $condition = null): self
    {
        $self = new self;
        $self->join = $join;
        $self->joinDqlAlias = $joinDqlAlias;
        $self->dqlAlias = $dqlAlias;
        $self->condition = $condition;
        $self->joinMethod = 'leftJoin';

        return $self;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias = null)
    {
        $queryBuilder->{$this->joinMethod}(
            $this->dqlAlias === false ? $this->join : sprintf('%s.%s', $this->dqlAlias ?? $dqlAlias, $this->join),
            $this->joinDqlAlias,
            $this->condition === null ? null : 'WITH',
            $this->resolveJoinCondition($queryBuilder, $this->condition)
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param mixed $condition
     * @return Andx|string|null
     */
    private function resolveJoinCondition(QueryBuilder $queryBuilder, $condition)
    {
        if ($condition instanceof Filter) {
            return $condition->getFilter($queryBuilder, $this->joinDqlAlias);
        } elseif (is_string($condition)) {
            return $condition;
        } elseif (is_array($condition)) {
            foreach ($condition as &$c) {
                $c = $this->getJoinCondition($queryBuilder, $c);
            }

            return new Andx(...$condition);
        }

        return null;
    }
}
