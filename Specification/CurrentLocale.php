<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Translatable\TranslatableListener;

class CurrentLocale implements QueryModifier
{
    /** @var bool */
    private $shouldIncludeUntranslated = false;

    /** @var string|null */
    private $dqlAlias;

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
                sprintf('%s.locale = :_current_locale', $translationsDqlAlias)
            )
            ->addSelect($translationsDqlAlias)
            ->setParameter('_current_locale', $this->resolveCurrentLocale($queryBuilder));

        if (!$this->shouldIncludeUntranslated) {
            $queryBuilder->andWhere(sprintf('%s.locale IS NOT NULL', $translationsDqlAlias));
        }
    }

    private function resolveCurrentLocale(QueryBuilder $queryBuilder): string
    {
        foreach ($queryBuilder->getEntityManager()->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TranslatableListener) {
                    $currentLocale = $listener->resolveCurrentLocale();
                    break 2;
                }
            }
        }

        if (!isset($currentLocale)) {
            throw new \RuntimeException('Cannot resolve current locale.');
        }
        
        return $currentLocale;
    }
}
