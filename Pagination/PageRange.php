<?php
namespace Vanio\DomainBundle\Pagination;

use Assert\Assertion;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;

class PageRange implements PageSpecification
{
    /** @var int */
    private $fromPage;

    /** @var int */
    private $toPage;

    /** @var int */
    private $recordsPerPage;

    /** @var int */
    private $recordsOnFirstPage;

    public function __construct(int $fromPage, int $toPage, int $recordsPerPage, int $recordsOnFirstPage = null)
    {
        $this->fromPage = max($fromPage, 1);
        $this->toPage = max($toPage, $this->fromPage);
        $this->recordsPerPage = max($recordsPerPage, 1);
        $this->recordsOnFirstPage = $recordsOnFirstPage ?? $recordsPerPage;
    }

    public static function create(string $value, int $recordsPerPage, int $recordsOnFirstPage = null): PageSpecification
    {
        list($fromPage, $toPage) = explode('-', $value) + [null, null];
        $fromPage = ctype_digit($fromPage) ? (int) $fromPage : 1;
        $toPage = ctype_digit($toPage) ? (int) $toPage : $fromPage;

        return new self($fromPage, $toPage, $recordsPerPage, $recordsOnFirstPage);
    }

    public function fromPage(): int
    {
        return $this->fromPage;
    }

    public function toPage(): int
    {
        return $this->toPage;
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
        return $this->fromPage === 1 ? 0 : ($this->fromPage - 2) * $this->recordsPerPage + $this->recordsOnFirstPage;
    }

    public function lastRecord(): int
    {
        return $this->toPage * $this->recordsPerPage + $this->recordsOnFirstPage - $this->recordsPerPage;
    }

    public function withRecordsPerPage(int $recordsPerPage, ?int $recordsOnFirstPage = null): self
    {
        return new self($this->fromPage, $this->toPage, $recordsPerPage, $recordsOnFirstPage);
    }

    /**
     * @param Query $query
     */
    public function modify(AbstractQuery $query)
    {
        Assertion::isInstanceOf($query, Query::class);
        $query
            ->setFirstResult($this->firstRecord())
            ->setMaxResults($this->lastRecord() - $this->firstRecord());
    }
}
