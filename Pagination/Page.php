<?php
namespace Vanio\DomainBundle\Pagination;

use Assert\Assertion;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Happyr\DoctrineSpecification\Result\ResultModifier;

class Page implements ResultModifier
{
    /** @var int */
    private $pageNumber;

    /** @var int */
    private $recordsPerPage;

    public function __construct(int $pageNumber, int $recordsPerPage)
    {
        $this->pageNumber = $pageNumber;
        $this->recordsPerPage = $recordsPerPage;
    }

    public function recordsPerPage(): int
    {
        return $this->recordsPerPage;
    }

    public function pageNumber(): int
    {
        return $this->pageNumber;
    }

    public function maxRecords(): int
    {
        return $this->pageNumber * $this->recordsPerPage;
    }

    /**
     * {@inheritDoc}
     * @param Query $query
     */
    public function modify(AbstractQuery $query)
    {
        Assertion::isInstanceOf($query, Query::class);
        $query
            ->setFirstResult($this->maxRecords() - $this->recordsPerPage)
            ->setMaxResults($this->recordsPerPage);
    }
}
