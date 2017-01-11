<?php
namespace Vanio\DomainBundle\Pagination;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter as FilterSpecification;
use Happyr\DoctrineSpecification\Result\ResultModifier;

class Filter implements FilterSpecification, ResultModifier
{
    /** @var OrderBy */
    private $orderBy;

    /** @var PageSpecification */
    private $page;

    /** @var string|null */
    private $dqlAlias;

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

    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias)
    {
        $this->orderBy()->modify($queryBuilder, $this->dqlAlias ?: $dqlAlias);
    }

    public function modify(AbstractQuery $query)
    {
        $this->page->modify($query);
    }
}
