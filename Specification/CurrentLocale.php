<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Translatable\TranslatableListener;

class CurrentLocale implements QueryModifier
{
    /**
     * @var bool
     */
    private $withUntranslated;

    /**
     * @var string|null
     */
    private $dqlAlias;

    public function __construct(bool $withUntranslated = false, $dqlAlias = null)
    {
        $this->withUntranslated = $withUntranslated;
        $this->dqlAlias = $dqlAlias;
    }

    public function withUntranslated(): self
    {
        return new self(true, $this->dqlAlias);
    }

    public function dqlAlias(): ?string
    {
        return $this->dqlAlias;
    }

    public function translationsAlias(): string
    {
        return sprintf('%s__translations', $this->dqlAlias);
    }

    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        $translationsAlias = $this->translationsAlias();
        $queryBuilder
            ->leftJoin(
                "$dqlAlias.translations",
                $translationsAlias,
                'WITH',
                "$translationsAlias.locale = :locale"
            )
            ->setParameter('locale', $this->resolveCurrentLocale($queryBuilder))
            ->addSelect($translationsAlias);

        if (!$this->withUntranslated) {
            $queryBuilder->where("$translationsAlias.locale IS NOT NULL");
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
