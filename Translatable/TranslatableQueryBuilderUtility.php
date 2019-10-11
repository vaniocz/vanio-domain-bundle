<?php
namespace Vanio\DomainBundle\Translatable;

use Doctrine\ORM\QueryBuilder;
use Vanio\DomainBundle\Doctrine\QueryBuilderUtility;

class TranslatableQueryBuilderUtility
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $translatableDqlAlias
     * @param bool $shouldIncludeUntranslated
     * @param string|bool|null $locale
     * @param string|bool|null $fallbackLocale
     * @param string|null $translationsDqlAlias
     * @param string|null $translatableClass
     */
    public static function joinTranslations(
        QueryBuilder $queryBuilder,
        string $translatableDqlAlias,
        bool $shouldIncludeUntranslated = false,
        $locale = null,
        $fallbackLocale = null,
        string $translationsDqlAlias = null,
        string $translatableClass = null
    ): void {
        $join = sprintf('%s.translations', $translatableDqlAlias);

        if ($translationsDqlAlias === null) {
            $translationsDqlAlias = sprintf('%s_translations', $translatableDqlAlias);
        }

        if (QueryBuilderUtility::findJoin($queryBuilder, $translationsDqlAlias)) {
            return;
        }

        if ($translatableClass === null) {
            $translatableClass = QueryBuilderUtility::resolveDqlAliasClasses($queryBuilder)[$translatableDqlAlias];
        }

        if ($locale === false) {
            $queryBuilder->leftJoin($join, $translationsDqlAlias);
        } else {
            $locale = self::resolveLocale($queryBuilder, $locale);

            if ($fallbackLocale !== false) {
                $fallbackLocale = self::resolveFallbackLocale($queryBuilder, $fallbackLocale, $translatableClass);
                $translationClass = $translatableClass::{'translationClass'}();
                $fallbackCondition = "
                    $translationsDqlAlias = TOP(
                        1,
                        SELECT _$translationsDqlAlias FROM $translationClass _$translationsDqlAlias
                        WHERE
                            _$translationsDqlAlias.translatable = $translatableDqlAlias AND (
                            _$translationsDqlAlias.locale IN ('$locale', '$fallbackLocale')
                        )
                        ORDER BY FIELD(_$translationsDqlAlias.locale, '$locale')
                    )
                ";
            }

            $queryBuilder
                ->leftJoin(
                    $join,
                    $translationsDqlAlias,
                    'WITH',
                    $fallbackLocale === false || $locale === $fallbackLocale
                        ? sprintf("%s.locale = '%s'", $translationsDqlAlias, $locale)
                        : $fallbackCondition
                )
                ->addSelect($translationsDqlAlias);
        }

        if (!$shouldIncludeUntranslated) {
            $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata($translatableClass);
            $conditions = [];

            foreach ($classMetadata->identifier as $property) {
                $conditions[] = sprintf('%s.%s IS NULL', $translatableDqlAlias, $property);
            }

            $queryBuilder->andWhere(sprintf(
                '(%s) OR %s.locale IS NOT NULL',
                implode(' AND ', $conditions),
                $translationsDqlAlias
            ));
        }
    }

    public static function resolveTranslatableListener(QueryBuilder $queryBuilder): TranslatableListener
    {
        foreach ($queryBuilder->getEntityManager()->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TranslatableListener) {
                    return $listener;
                }
            }
        }

        throw new \RuntimeException('The translatable listener could not be found.');
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $locale
     * @return string|null
     */
    private static function resolveLocale(QueryBuilder $queryBuilder, string $locale = null)
    {
        if ($locale === null) {
            if (!$locale = self::resolveTranslatableListener($queryBuilder)->resolveCurrentLocale()) {
                throw new \RuntimeException('Cannot resolve current locale.');
            }
        }

        return $locale;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $fallbackLocale
     * @param string $translatableClass
     * @return string|bool
     */
    private static function resolveFallbackLocale(
        QueryBuilder $queryBuilder,
        string $fallbackLocale = null,
        string $translatableClass
    ) {
        if ($fallbackLocale === null) {
            if ($translatableClass::{'shouldFallbackToDefaultLocale'}()) {
                if (!$fallbackLocale = self::resolveTranslatableListener($queryBuilder)->resolveDefaultLocale()) {
                    throw new \RuntimeException('Cannot resolve fallback locale.');
                }

                return $fallbackLocale;
            }

            return false;
        }

        return $fallbackLocale;
    }
}
