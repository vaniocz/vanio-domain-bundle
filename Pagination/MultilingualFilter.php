<?php
namespace Vanio\DomainBundle\Pagination;

use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Logic\AndX;
use Vanio\DomainBundle\Specification\Locale;
use Vanio\DomainBundle\Specification\WithTranslations;

class MultilingualFilter extends BaseSpecification
{
    /** @var Filter */
    private $filter;

    /** @var Locale */
    private $locale;

    /** @var string|null */
    private $dqlAlias;

    public function __construct(Filter $filter, Locale $locale, string $dqlAlias = null)
    {
        parent::__construct($dqlAlias);
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

    public function withDqlAlias(string $dqlAlias = null): self
    {
        return new self($this->filter, $this->locale, $dqlAlias);
    }

    public function getSpec(): AndX
    {
        return new AndX($this->locale, new WithTranslations, $this->filter);
    }
}
