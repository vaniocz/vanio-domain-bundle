<?php
namespace Vanio\DomainBundle\Pagination;

use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Logic\AndX;
use Vanio\DomainBundle\Specification\WithTranslations;

class MultilingualFilter extends BaseSpecification
{
    /** @var Filter */
    private $filter;

    /** @var string */
    private $locale;

    /** @var string|null */
    private $dqlAlias;

    public function __construct(Filter $filter, string $locale, string $dqlAlias = null)
    {
        $this->filter = $filter;
        $this->locale = $locale;
        $this->dqlAlias = $dqlAlias;
    }

    public function filter(): Filter
    {
        return $this->filter;
    }

    public function locale(): string
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

    public function getSpec(): AndX
    {
        return new AndX(new WithTranslations($this->locale), $this->filter);
    }
}
