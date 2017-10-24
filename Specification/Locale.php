<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;

class Locale implements QueryModifier
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var bool
     */
    private $withUntranslated;

    /**
     * @var string|null
     */
    private $dqlAlias;

    public function __construct(string $locale, bool $withUntranslated = false, $dqlAlias = null)
    {
        $this->locale = $locale;
        $this->withUntranslated = $withUntranslated;
        $this->dqlAlias = $dqlAlias;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function withUntranslated(): self
    {
        return new self($this->locale, true, $this->dqlAlias);
    }

    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        $queryBuilder
            ->leftJoin(sprintf('%s.%s', $this->dqlAlias ?? $dqlAlias, 'translations'), '__t', 'with', '__t.locale = :locale')
            ->setParameter('locale', $this->locale)
            ->addSelect('__t');
        if (!$this->withUntranslated) {
            $queryBuilder->where('__t.locale IS NOT NULL');
        }
    }
}
