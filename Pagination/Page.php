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

    public function __construct(int $pageNumber, int $recordsPerPage)
    {
        $this->pageNumber = max($pageNumber, 1);
        $this->recordsPerPage = max($recordsPerPage, 1);
    }

    public static function create(string $value, int $recordsPerPage): PageSpecification
    {
        return new self(ctype_digit($value) ? max((int) $value, 1) : 1, $recordsPerPage);
    }

    public function pageNumber(): int
    {
        return $this->pageNumber;
    }

    public function recordsPerPage(): int
    {
        return $this->recordsPerPage;
    }

    public function firstRecord(): int
    {
        return $this->lastRecord() - $this->recordsPerPage;
    }

    public function lastRecord(): int
    {
        return $this->pageNumber * $this->recordsPerPage;
    }

    /**
     * @param Query $query
     */
    public function modify(AbstractQuery $query)
    {
        Assertion::isInstanceOf($query, Query::class);
        $query
            ->setFirstResult($this->firstRecord())
            ->setMaxResults($this->recordsPerPage);
    }
}
