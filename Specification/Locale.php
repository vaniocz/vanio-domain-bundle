<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\Query\Expr;
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

    public function withUntranslated()
    {
        return new self($this->locale, true, $this->dqlAlias);
    }

    public function modify(QueryBuilder $qb, $dqlAlias)
    {
        $qb->leftJoin(sprintf('%s.%s', $this->dqlAlias ?? $dqlAlias, 'translations'), 't', Expr\Join::WITH, 't.locale = :locale')
            ->setParameter('locale', $this->locale);
        if (!$this->withUntranslated) {
            $qb->where('t.locale IS NOT NULL');
        }
    }
}
