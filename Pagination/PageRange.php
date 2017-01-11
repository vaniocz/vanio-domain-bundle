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

    public function __construct(int $fromPage, int $toPage, int $recordsPerPage)
    {
        $this->fromPage = max($fromPage, 1);
        $this->toPage = max($toPage, $this->fromPage);
        $this->recordsPerPage = max($recordsPerPage, 1);
    }

    public static function create(string $value, int $recordsPerPage): PageSpecification
    {
        list($fromPage, $toPage) = explode('-', $value) + [null, null];
        $fromPage = ctype_digit($fromPage) ? (int) $fromPage : 1;
        $toPage = ctype_digit($toPage) ? (int) $toPage : $fromPage;

        return new self($fromPage, $toPage, $recordsPerPage);
    }

    public function fromPage(): int
    {
        return $this->fromPage;
    }

    public function toPage(): int
    {
        return $this->fromPage;
    }

    public function recordsPerPage(): int
    {
        return $this->recordsPerPage;
    }

    public function firstRecord(): int
    {
        return $this->recordsPerPage * ($this->fromPage - 1);
    }

    public function lastRecord(): int
    {
        return $this->toPage * $this->recordsPerPage;
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
