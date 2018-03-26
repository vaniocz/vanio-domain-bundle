<?php
namespace Vanio\DomainBundle\Pagination;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter as FilterSpecification;
use Happyr\DoctrineSpecification\Result\ResultModifier;
use Vanio\DomainBundle\Specification\Locale;
use Vanio\DomainBundle\Specification\WithTranslations;

class MultilingualFilter implements FilterSpecification, ResultModifier
{
    /** @var Filter */
    private $filter;

    /** @var Locale */
    private $locale;

    /** @var string|null */
    private $dqlAlias;

    public function __construct(Filter $filter, Locale $locale, string $dqlAlias = null)
    {
        $this->filter = $filter;
        $this->locale = $locale;
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
     * @param QueryBuilder $queryBuilder
     * @param string $dqlAlias
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias)
    {
        $this->locale()->modify($queryBuilder, $this->dqlAlias ?? $dqlAlias);
        (new WithTranslations)->modify($queryBuilder, $this->dqlAlias ?? $dqlAlias);
        $this->filter()->getFilter($queryBuilder, $this->dqlAlias ?? $dqlAlias);
    }

    public function modify(AbstractQuery $query)
    {
        $this->filter()->modify($query);
    }
}
