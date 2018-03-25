<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;

class With implements QueryModifier
{
    /** @var string */
    private $field;

    /** @var string */
    private $newAlias;

    /** @var string|null */
    private $dqlAlias;

    /** @var string|null */
    private $condition;

    /** @var bool */
    private $isNullAllowed = false;

    /**
     * @param string $field
     * @param string $newAlias
     * @param string $dqlAlias
     */
    public function __construct(string $field, string $newAlias, string $dqlAlias = null, string $condition = null)
    {
        $this->field = $field;
        $this->newAlias = $newAlias;
        $this->dqlAlias = $dqlAlias;
        $this->condition = $condition;
    }

    public static function withAllowedNull(
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
            ->addSelect($this->newAlias)
            ->$joinMethod(
                sprintf('%s.%s', $this->dqlAlias ?? $dqlAlias, $this->field),
                $this->newAlias,
                'WITH',
                $this->condition
            );
    }
}
