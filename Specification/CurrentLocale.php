<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Translatable\TranslatableQueryBuilderUtility;

class CurrentLocale implements QueryModifier
{
    /** @var bool */
    private $shouldIncludeUntranslated = false;

    /** @var string|null */
    private $dqlAlias;

    /**
     * @param string|null $dqlAlias
     */
    public function __construct(string $dqlAlias = null)
    {
        $this->dqlAlias = $dqlAlias;
    }

    public static function includeUntranslated(string $dqlAlias = null): self
    {
        $self = new self($dqlAlias);
        $self->shouldIncludeUntranslated = true;

        return $self;
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
        return new self($dqlAlias);
    }

    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        TranslatableQueryBuilderUtility::joinTranslations(
            $queryBuilder,
            $this->dqlAlias ?? $dqlAlias,
            $this->shouldIncludeUntranslated
        );
    }
}
