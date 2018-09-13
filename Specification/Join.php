<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Join as JoinExpression;
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
    private $joinType;

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
        $self->joinType = JoinExpression::INNER_JOIN;

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
        $self->joinType = JoinExpression::LEFT_JOIN;

        return $self;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias = null)
    {
        $join = $this->dqlAlias === false ? $this->join : sprintf('%s.%s', $this->dqlAlias ?? $dqlAlias, $this->join);
        $condition = $this->resolveJoinCondition($queryBuilder, $this->condition);

        if ($this->findJoin($queryBuilder, $join, $condition)) {
            return;
        }

        $joinMethod = $this->joinType === JoinExpression::LEFT_JOIN ? 'leftJoin' : 'innerJoin';
        $queryBuilder->$joinMethod(
            $join,
            $this->joinDqlAlias,
            $this->condition === null ? null : 'WITH',
            $condition
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
                $c = $this->resolveJoinCondition($queryBuilder, $c);
            }

            return new Andx(...$condition);
        }

        return null;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $join
     * @param Andx|string|null $condition
     * @return Join|null
     */
    private function findJoin(QueryBuilder $queryBuilder, string $join, $condition)
    {
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $dqlPart) {
                /** @var JoinExpression $dqlPart */
                if ($dqlPart->getAlias() !== $this->joinDqlAlias) {
                    continue;
                } elseif (
                    $dqlPart->getJoinType() === $this->joinType
                    && $dqlPart->getJoin() === $join
                    && (string) $dqlPart->getCondition() === (string) $condition
                    && ($dqlPart->getConditionType() ?? 'WITH') === 'WITH'
                ) {
                    return $dqlPart;
                }

                throw new \InvalidArgumentException(sprintf(
                    'Different DQL join alias "%s" is already defined.',
                    $this->dqlAlias
                ));
            }
        }

        return null;
    }
}
