<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Happyr\DoctrineSpecification\Result\ResultModifier;
use Vanio\DomainBundle\Translatable\TranslatableWalker;

class JoinTranslations implements ResultModifier
{
    /** @var string[]|null */
    private $classes;

    /** @var string|bool */
    private $locale;

    /** @var string|bool */
    private $fallbackLocale;

    /** @var bool */
    private $innerJoin;

    /**
     * @param string[]|null $types
     * @param string|bool $locale
     * @param string|bool $fallbackLocale
     * @param bool $innerJoin
     */
    public function __construct(array $types = null, $locale = true, $fallbackLocale = false, bool $innerJoin = false)
    {
        $this->classes = $types;
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
        $this->innerJoin = $innerJoin;
    }

    public function modify(AbstractQuery $query)
    {
        $treeWalkers = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS) ?: [];
        $treeWalkers[] = TranslatableWalker::class;
        $query
            ->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $treeWalkers)
            ->setHint(TranslatableWalker::HINT_LOCALE, $this->locale)
            ->setHint(TranslatableWalker::HINT_FALLBACK_LOCALE, $this->fallbackLocale)
            ->setHint(TranslatableWalker::HINT_INNER_JOIN, $this->innerJoin)
            ->setHint(TranslatableWalker::HINT_CLASSES, $this->classes);
    }
}
