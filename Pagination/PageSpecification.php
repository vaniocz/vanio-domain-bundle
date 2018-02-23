<?php
namespace Vanio\DomainBundle\Pagination;

use Happyr\DoctrineSpecification\Result\ResultModifier;

interface PageSpecification extends ResultModifier
{
    public static function create(string $value, int $recordsPerPage, int $recordsOnFirstPage = null): self;

    public function recordsPerPage(): int;

    public function recordsOnFirstPage(): int;

    public function firstRecord(): int;

    public function lastRecord(): int;
}
