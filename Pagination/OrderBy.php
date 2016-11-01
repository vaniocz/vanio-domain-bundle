<?php
namespace Vanio\DomainBundle\Pagination;

use Happyr\DoctrineSpecification\Query\OrderBy as BaseOrderBy;

class OrderBy extends BaseOrderBy
{
    public function __construct(string $field, string $direction = 'ASC', string $dqlAlias = null)
    {
        parent::__construct($field, strtoupper($direction), $dqlAlias);
    }

    public function field(): string
    {
        return $this->field;
    }

    public function direction(): string
    {
        return $this->order;
    }
}
