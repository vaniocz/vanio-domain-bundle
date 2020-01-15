<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Translatable\TranslatableQueryBuilderUtility;

class Locale implements QueryModifier
{
    /** @var string */
    private $locale;

    /** @var string|bool|null */
    private $fallbackLocale;

    /** @var bool */
    private $shouldIncludeUntranslated = false;

    /** @var string|null */
    private $dqlAlias;

    /**
     * @param string $locale
     * @param string|bool|null $fallbackLocale
     * @param string|null $dqlAlias
     */
    public function __construct(string $locale, $fallbackLocale = null, string $dqlAlias = null)
    {
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
        $this->dqlAlias = $dqlAlias;
    }

    /**
     * @param string $locale
     * @param string|bool|null $fallbackLocale
     * @param string|null $dqlAlias
     * @return $this
     */
    public static function includeUntranslated(string $locale, $fallbackLocale = null, string $dqlAlias = null): self
    {
        $self = new self($locale, $fallbackLocale, $dqlAlias);
        $self->shouldIncludeUntranslated = true;

        return $self;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function language(): string
    {
        return substr($this->locale, 0, 2);
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
        $self = new self($this->locale, $this->fallbackLocale, $this->dqlAlias);
        $self->shouldIncludeUntranslated = true;

        return $self;
    }

    public function withDqlAlias(string $dqlAlias = null): self
    {
        $self = new self($this->locale, $dqlAlias);
        $self->shouldIncludeUntranslated = $this->shouldIncludeUntranslated;

        return $self;
    }

    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        TranslatableQueryBuilderUtility::joinTranslations(
            $queryBuilder,
            $this->dqlAlias ?? $dqlAlias,
            $this->shouldIncludeUntranslated,
            $this->locale,
            $this->fallbackLocale
        );
    }

    public function __toString(): string
    {
        return $this->locale();
    }
}
