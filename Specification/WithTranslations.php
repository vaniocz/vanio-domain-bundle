<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
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

    /** @var EntityManager */
    private $entityManager;

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
        $this->entityManager = $queryBuilder->getEntityManager();
        $this->isLocalesParameterSet = false;
        $dqlAliasClasses = $this->resolveDqlAliasClasses();
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
                    $this->joinTranslations(sprintf('%s.translations', $selectedDqlAlias), $translationsDqlAlias);
                }
            }
        }
    }

    /**
     * @return string[]
     */
    private function resolveDqlAliasClasses(): array
    {
        $dqlAliasClasses = [];

        /** @var From $from */
        foreach ($this->queryBuilder->getDQLPart('from') as $from) {
            $dqlAliasClasses[$from->getAlias()] = $from->getFrom();
        }

        foreach ($this->queryBuilder->getDQLPart('join') as $joins) {
            /** @var Join $join */
            foreach ($joins as $join) {
                list($dqlAlias, $association) = explode('.', $join->getJoin(), 2) + [null, null];

                if ($association) {
                    $classMetadata = $this->entityManager->getClassMetadata($dqlAliasClasses[$dqlAlias]);
                    $dqlAliasClasses[$join->getAlias()] = $classMetadata->getAssociationTargetClass($association);
                } else {
                    $dqlAliasClasses[$join->getAlias()] = $join->getJoin();
                }
            }
        }

        return $dqlAliasClasses;
    }

    private function joinTranslations(string $join, string $dqlAlias)
    {
        if ($this->locale === false) {
            $this->queryBuilder->leftJoin($join, $dqlAlias);
        } else {
            $this->queryBuilder->leftJoin(
                $join,
                $dqlAlias,
                'WITH',
                sprintf('%s.locale IN (:_with_translations_locales)', $dqlAlias)
            );

            if (!$this->isLocalesParameterSet) {
                $this->queryBuilder->setParameter('_with_translations_locales', $this->resolveLocales());
                $this->isLocalesParameterSet = true;
            }
        }

        $this->queryBuilder->addSelect($dqlAlias);

        if (!$this->shouldIncludeUntranslated) {
            $this->queryBuilder->andWhere(sprintf('%s.locale IS NOT NULL', $dqlAlias));
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
            foreach ($this->entityManager->getEventManager()->getListeners() as $listeners) {
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
