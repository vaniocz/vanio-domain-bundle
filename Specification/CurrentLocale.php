<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Translatable\TranslatableQueryBuilderUtility;

class CurrentLocale implements QueryModifier
{
    /** @var string|bool|null */
    private $fallbackLocale;

    /** @var bool */
    private $shouldIncludeUntranslated = false;

    /** @var string|null */
    private $dqlAlias;

    /**
     * @param string|bool|null $fallbackLocale
     * @param string|null $dqlAlias
     */
    public function __construct($fallbackLocale = null, string $dqlAlias = null)
    {
        $this->fallbackLocale = $fallbackLocale;
        $this->dqlAlias = $dqlAlias;
    }

    /**
     * @param string|bool|null $fallbackLocale
     * @param string|null $dqlAlias
     * @return $this
     */
    public static function includeUntranslated($fallbackLocale = null, string $dqlAlias = null): self
    {
        $self = new self($fallbackLocale, $dqlAlias);
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
