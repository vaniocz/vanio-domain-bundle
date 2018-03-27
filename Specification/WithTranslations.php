<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Doctrine\QueryBuilderUtility;
use Vanio\DomainBundle\Translatable\Translatable;
use Vanio\DomainBundle\Translatable\TranslatableListener;

class WithTranslations implements QueryModifier
{
    /** @var string|bool */
    private $locale;

    /** @var string|bool */
    private $fallbackLocale;

    /** @var bool */
    private $shouldIncludeUntranslated;

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var TranslatableListener|null */
    private $translatableListener;

    /** @var bool|null */
    private $isLocalesParameterSet;

    /**
     * @param string|bool $locale
     * @param string|bool $fallbackLocale
     */
    public function __construct($locale = true, $fallbackLocale = false)
    {
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
    }

    public static function includeUntranslated($locale = true, $fallbackLocale = false): self
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
        $this->queryBuilder = $queryBuilder;
        $this->isLocalesParameterSet = false;
        $dqlAliasClasses = QueryBuilderUtility::resolveDqlAliasClasses($queryBuilder);
        $selectedDqlAliases = [];

        /** @var Select $select */
        foreach ($this->queryBuilder->getDQLPart('select') as $select) {
            foreach ($select->getParts() as $part) {
                $selectedDqlAliases[$part] = $part;
            }
        }

        foreach ($selectedDqlAliases as $selectedDqlAlias) {
            if ($class = $dqlAliasClasses[$selectedDqlAlias] ?? null) {
                $translationsDqlAlias = sprintf('%s_translations', $selectedDqlAlias);

                if (!isset($selectedDqlAliases[$translationsDqlAlias]) && is_a($class, Translatable::class, true)) {
                    $this->joinTranslations($class, $selectedDqlAlias, $translationsDqlAlias);
                }
            }
        }
    }

    private function joinTranslations(
        string $translatableClass,
        string $translatableDqlAlias,
        string $translationsDqlAlias
    ) {
        $join = sprintf('%s.translations', $translatableDqlAlias);

        if ($this->locale === false) {
            $this->queryBuilder->leftJoin($join, $translationsDqlAlias);
        } else {
            $this->queryBuilder->leftJoin(
                $join,
                $translationsDqlAlias,
                'WITH',
                sprintf('%s.locale IN (:_with_translations_locales)', $translationsDqlAlias)
            );

            if (!$this->isLocalesParameterSet) {
                $this->queryBuilder->setParameter('_with_translations_locales', $this->resolveLocales());
                $this->isLocalesParameterSet = true;
            }
        }

        $this->queryBuilder->addSelect($translationsDqlAlias);

        if (!$this->shouldIncludeUntranslated) {
            $classMetadata = $this->queryBuilder->getEntityManager()->getClassMetadata($translatableClass);
            $conditions = [];

            foreach ($classMetadata->identifier as $property) {
                $conditions[] = sprintf('%s.%s IS NULL', $translatableDqlAlias, $property);
            }

            $this->queryBuilder->andWhere(sprintf(
                '(%s) OR %s.locale IS NOT NULL',
                implode(' AND ', $conditions),
                $translationsDqlAlias
            ));
        }
    }

    /**
     * @return string[]
     */
    private function resolveLocales(): array
    {
        $locales = [];

        if ($this->locale !== false) {
            $locales[] = $this->locale === true
                ? $this->translatableListener()->resolveCurrentLocale()
                : $this->locale;
        }

        if ($this->fallbackLocale !== false) {
            $locales[] = $this->fallbackLocale === true
                ? $this->translatableListener()->resolveDefaultLocale()
                : $this->fallbackLocale;
        }

        return $locales;
    }

    private function translatableListener(): TranslatableListener
    {
        if ($this->translatableListener === null) {
            foreach ($this->queryBuilder->getEntityManager()->getEventManager()->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof TranslatableListener) {
                        $this->translatableListener = $listener;

                        return $listener;
                    }
                }
            }

            throw new \RuntimeException('The translatable listener could not be found.');
        }

        return $this->translatableListener;
    }
}
