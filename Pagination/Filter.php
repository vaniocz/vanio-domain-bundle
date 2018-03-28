<?php
namespace Vanio\DomainBundle\Pagination;

use Happyr\DoctrineSpecification\Logic\AndX;
use Vanio\DomainBundle\Doctrine\Specification;

class Filter extends Specification
{
    /** @var OrderBy */
    private $orderBy;

    /** @var PageSpecification */
    private $page;

    public function __construct(OrderBy $orderBy, PageSpecification $page, string $dqlAlias = null)
    {
        $this->orderBy = $orderBy;
        $this->page = $page;
        $this->dqlAlias = $dqlAlias;
    }

    public function orderBy(): OrderBy
    {
        return $this->orderBy;
    }

    public function page(): PageSpecification
    {
        return $this->page;
    }

    public function buildSpecification(string $dqlAlias): AndX
    {
        return new AndX($this->orderBy, $this->page);
    }
}
