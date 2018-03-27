<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Doctrine\QueryBuilderUtility;

class Locale implements QueryModifier
{
    /** @var string */
    private $locale;

    /** @var bool */
    private $shouldIncludeUntranslated = false;

    /** @var string|null */
    private $dqlAlias;

    public function __construct(string $locale, string $dqlAlias = null)
    {
        $this->locale = $locale;
        $this->dqlAlias = $dqlAlias;
    }

    public static function includeUntranslated(string $locale, string $dqlAlias = null): self
    {
        $self = new self($locale, $dqlAlias);
        $self->shouldIncludeUntranslated = true;

        return $self;
    }

    public function withIncludedUntranslated(): self
    {
        $self = new self($this->locale, $this->dqlAlias);
        $self->shouldIncludeUntranslated = true;

        return $self;
    }

    public function locale(): string
    {
        return $this->locale;
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
        return new self($this->locale, $dqlAlias);
    }

    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        $translationsDqlAlias = sprintf('%s_translations', $dqlAlias);
        $queryBuilder
            ->leftJoin(
                sprintf('%s.translations', $dqlAlias),
                $translationsDqlAlias,
                'WITH',
                sprintf('%s.locale = :_locale', $translationsDqlAlias)
            )
            ->addSelect($translationsDqlAlias)
            ->setParameter('_locale', $this->locale);

        if (!$this->shouldIncludeUntranslated) {
            $class = QueryBuilderUtility::resolveDqlAliasClasses($queryBuilder)[$dqlAlias];
            $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata($class);
            $conditions = [];

            foreach ($classMetadata->identifier as $property) {
                $conditions[] = sprintf('%s.%s IS NULL', $dqlAlias, $property);
            }

            $queryBuilder->andWhere(sprintf(
                '(%s) OR %s.locale IS NOT NULL',
                implode(' AND ', $conditions),
                $translationsDqlAlias
            ));

            $queryBuilder->andWhere(sprintf('%s.locale IS NOT NULL', $translationsDqlAlias));
        }
    }

    public function __toString(): string
    {
        return $this->locale();
    }
}
