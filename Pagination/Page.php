<?php
namespace Vanio\DomainBundle\Pagination;

use Assert\Assertion;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;

class Page implements PageSpecification
{
    /** @var int */
    private $pageNumber;

    /** @var int */
    private $recordsPerPage;

    /** @var int */
    private $recordsOnFirstPage;

    public function __construct(int $pageNumber, int $recordsPerPage, int $recordsOnFirstPage = null)
    {
        $this->pageNumber = max($pageNumber, 1);
        $this->recordsPerPage = max($recordsPerPage, 1);
        $this->recordsOnFirstPage = $recordsOnFirstPage ?? $recordsPerPage;
    }

    public static function create(string $value, int $recordsPerPage, int $recordsOnFirstPage = null): PageSpecification
    {
        return new self(ctype_digit($value) ? max((int) $value, 1) : 1, $recordsPerPage, $recordsOnFirstPage);
    }

    public function pageNumber(): int
    {
        return $this->pageNumber;
    }

    public function recordsPerPage(): int
    {
        return $this->recordsPerPage;
    }

    public function recordsOnFirstPage(): int
    {
        return $this->recordsOnFirstPage;
    }

    public function firstRecord(): int
    {
        return $this->pageNumber === 1 ? 0 : $this->lastRecord() - $this->recordsPerPage;
    }

    public function lastRecord(): int
    {
        return $this->pageNumber * $this->recordsPerPage + $this->recordsOnFirstPage - $this->recordsPerPage;
    }

    public function maximalPage(int $recordsCount): int
    {
        return ceil(max($recordsCount - $this->recordsOnFirstPage, 0) / $this->recordsPerPage + 1);
    }

    /**
     * @param Query $query
     */
    public function modify(AbstractQuery $query)
    {
        Assertion::isInstanceOf($query, Query::class);
        $query
            ->setFirstResult($this->firstRecord())
            ->setMaxResults($this->pageNumber === 1 ? $this->recordsOnFirstPage : $this->recordsPerPage);
    }
}
