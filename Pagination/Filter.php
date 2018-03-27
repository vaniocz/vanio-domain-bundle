<?php
namespace Vanio\DomainBundle\Pagination;

use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Logic\AndX;

class Filter extends BaseSpecification
{
    /** @var OrderBy */
    private $orderBy;

    /** @var PageSpecification */
    private $page;

    public function __construct(OrderBy $orderBy, PageSpecification $page, string $dqlAlias = null)
    {
        parent::__construct($dqlAlias);
        $this->orderBy = $orderBy;
        $this->page = $page;
    }

    public function orderBy(): OrderBy
    {
        return $this->orderBy;
    }

    public function page(): PageSpecification
    {
        return $this->page;
    }

    public function getSpec(): AndX
    {
        return new AndX($this->orderBy, $this->page);
    }
}
