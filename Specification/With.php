<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;

class With implements QueryModifier
{
    /** @var string */
    private $field;

    /** @var string */
    private $joinDqlAlias;

    /** @var string|null */
    private $dqlAlias;

    /** @var string|null */
    private $condition;

    /** @var bool */
    private $isNullAllowed = false;

    public function __construct(string $field, string $joinDqlAlias, string $dqlAlias = null, string $condition = null)
    {
        $this->field = $field;
        $this->joinDqlAlias = $joinDqlAlias;
        $this->dqlAlias = $dqlAlias;
        $this->condition = $condition;
    }

    public static function allowNull(
        string $field,
        string $newAlias,
        string $dqlAlias = null,
        string $condition = null
    ): self {
        $self = new self($field, $newAlias, $dqlAlias, $condition);
        $self->isNullAllowed = true;

        return $self;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias = null)
    {
        $joinMethod = $this->isNullAllowed ? 'leftJoin' : 'innerJoin';
        $queryBuilder
            ->addSelect($this->joinDqlAlias)
            ->$joinMethod(
                sprintf('%s.%s', $this->dqlAlias ?? $dqlAlias, $this->field),
                $this->joinDqlAlias,
                'WITH',
                $this->condition
            );
    }
}
