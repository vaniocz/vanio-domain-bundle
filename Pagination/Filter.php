<?php
namespace Vanio\DomainBundle\Pagination;

use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Logic\AndX;

class Filter extends BaseSpecification
{
    /** @var OrderBy */
    private $orderBy;

    /** @var Page */
    private $page;

    public function __construct(OrderBy $orderBy, Page $page, string $dqlAlias = null)
    {
        parent::__construct($dqlAlias);
        $this->orderBy = $orderBy;
        $this->page = $page;
    }

    public function orderBy(): OrderBy
    {
        return $this->orderBy;
    }

    public function page(): Page
    {
        return $this->page;
    }

    public function getSpec()
    {
        return new AndX($this->orderBy, $this->page);
    }
}
