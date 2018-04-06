<?php
namespace Vanio\DomainBundle\Pagination;

use Happyr\DoctrineSpecification\Logic\AndX;
use Vanio\DomainBundle\Doctrine\Specification;
use Vanio\DomainBundle\Specification\Locale;
use Vanio\DomainBundle\Specification\WithTranslations;

class MultilingualFilter extends Specification
{
    /** @var Filter */
    private $filter;

    /** @var Locale */
    private $locale;

    public function __construct(Filter $filter, Locale $locale, string $dqlAlias = null)
    {
        $this->filter = $filter;
        $this->locale = $locale->withDqlAlias($dqlAlias);
        $this->dqlAlias = $dqlAlias;
    }

    public function filter(): Filter
    {
        return $this->filter;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function orderBy(): OrderBy
    {
        return $this->filter()->orderBy();
    }

    public function page(): PageSpecification
    {
        return $this->filter()->page();
    }

    /**
     * @return string|null
     */
    public function dqlAlias()
    {
        return $this->dqlAlias;
    }

    public function withIncludedUntranslated(): self
    {
        return new self($this->filter, $this->locale->withIncludedUntranslated(), $this->dqlAlias);
    }

    public function withDqlAlias(string $dqlAlias = null): self
    {
        return new self($this->filter, $this->locale, $dqlAlias);
    }

    public function buildSpecification(string $dqlAlias): AndX
    {
        return new AndX($this->locale, new WithTranslations, $this->filter);
    }
}
