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

    public function locale(): string
    {
        return $this->locale;
    }

    public function withUntranslated(): self
    {
        return new self($this->locale, true, $this->dqlAlias);
    }

    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        if ($this->dqlAlias !== null) {
            $this->dqlAlias = $dqlAlias;
        }

        $queryBuilder
            ->leftJoin("$dqlAlias.translations", '__t', 'WITH', '__t.locale = :locale')
            ->setParameter('locale', $this->locale)
            ->addSelect('__t');

        if (!$this->withUntranslated) {
            $queryBuilder->where('__t.locale IS NOT NULL');
        }
    }

    public function __toString(): string
    {
        return $this->locale();
    }
}
