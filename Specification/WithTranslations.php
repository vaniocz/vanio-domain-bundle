<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Doctrine\QueryBuilderUtility;
use Vanio\DomainBundle\Translatable\Translatable;
use Vanio\DomainBundle\Translatable\TranslatableQueryBuilderUtility;

class WithTranslations implements QueryModifier
{
    /** @var string|bool|null */
    private $locale;

    /** @var string|bool|null */
    private $fallbackLocale;

    /** @var bool */
    private $shouldIncludeUntranslated = false;

    /**
     * @param string|bool|null $locale
     * @param string|bool|null $fallbackLocale
     */
    public function __construct($locale = null, $fallbackLocale = null)
    {
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
    }

    /**
     * @param string|bool|null $locale
     * @param string|bool|null $fallbackLocale
     * @return $this
     */
    public static function includeUntranslated($locale = true, $fallbackLocale = null): self
    {
        $self = new self($locale, $fallbackLocale);
        $self->shouldIncludeUntranslated = true;

        return $self;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias = null)
    {
        $dqlAliasClasses = QueryBuilderUtility::resolveDqlAliasClasses($queryBuilder);
        $selectedDqlAliases = [];

        /** @var Select $select */
        foreach ($queryBuilder->getDQLPart('select') as $select) {
            foreach ($select->getParts() as $part) {
                $selectedDqlAliases[$part] = $part;
            }
        }

        foreach ($selectedDqlAliases as $selectedDqlAlias) {
            if ($class = $dqlAliasClasses[$selectedDqlAlias] ?? null) {
                $translationsDqlAlias = sprintf('%s_translations', $selectedDqlAlias);

                if (!isset($selectedDqlAliases[$translationsDqlAlias]) && is_a($class, Translatable::class, true)) {
                    TranslatableQueryBuilderUtility::joinTranslations(
                        $queryBuilder,
                        $selectedDqlAlias,
                        $this->shouldIncludeUntranslated,
                        $this->locale,
                        $this->fallbackLocale,
                        $translationsDqlAlias,
                        $class
                    );
                }
            }
        }
    }
}
