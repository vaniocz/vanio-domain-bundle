<?php
namespace Vanio\DomainBundle\Model\Translatable;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Util\ClassUtils;
use Vanio\DomainBundle\Doctrine\CollectionDecorator;

class TranslatableCollection implements CollectionDecorator, Selectable
{
    /** @var Collection */
    private $collection;

    /** @var Translatable */
    private $translatable;

    public function __construct(Collection $collection, Translatable $translatable)
    {
        $this->collection = $collection;
        //TODO: replace
        $this->translatable = $translatable;
    }

    /**
     * @param Translation $translation
     * @return bool
     */
    public function add($translation): bool
    {
        $this->set($translation->locale(), $translation);

        return true;
    }

    public function clear()
    {
        $this->collection->clear();
    }

    /**
     * @param Translation $translation
     * @return bool
     */
    public function contains($translation): bool
    {
        return $this->collection->contains($translation);
    }

    public function isEmpty(): bool
    {
        return $this->collection->isEmpty();
    }


    /**
     * @param string $locale
     * @return Translation|null
     */
    public function remove($locale)
    {
        return $this->collection->remove($locale);
    }

    /**
     * @param Translation $translation
     * @return bool
     */
    public function removeElement($translation): bool
    {
        return $this->collection->removeElement($translation);
    }

    /**
     * @param string $locale
     * @return bool
     */
    public function containsKey($locale): bool
    {
        return $this->collection->containsKey($locale);
    }

    /**
     * @param string $locale
     * @return Translation|null
     */
    public function get($locale)
    {
        return $this->collection->get($locale);
    }

    /**
     * @return string[]
     */
    public function getKeys(): array
    {
        return $this->collection->getKeys();
    }

    /**
     * @return Translation[]
     */
    public function getValues(): array
    {
        return $this->collection->getValues();
    }

    /**
     * @param string $locale
     * @param Translation $translation
     */
    public function set($locale, $translation)
    {
        $translationClass = $this->translatable::translationClass();

        if (!$translation instanceof $translationClass) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid translation class "%". Class "%s" must contain only translations of class "%s".',
                ClassUtils::getClass($translation),
                ClassUtils::getClass($this->translatable),
                $translationClass
            ));
        }

        /** @noinspection PhpInternalEntityUsedInspection */
        $translation
            ->setLocale($locale)
            ->setTranslatable($this->translatable);
        $this->collection->set($locale, $translation);
    }

    /**
     * @return Translation[]
     */
    public function toArray(): array
    {
        return $this->collection->toArray();
    }

    /**
     * @return Translation|bool
     */
    public function first()
    {
        return $this->collection->first();
    }

    /**
     * @return Translation|bool
     */
    public function last()
    {
        return $this->collection->last();
    }

    /**
     * @return string|null
     */
    public function key()
    {
        return $this->collection->key();
    }

    /**
     * @return Translation|bool
     */
    public function current()
    {
        return $this->collection->current();
    }

    /**
     * @return Translation|bool
     */
    public function next()
    {
        return $this->collection->next();
    }

    public function exists(\Closure $predicate): bool
    {
        return $this->collection->exists($predicate);
    }

    /**
     * @param \Closure $predicate
     * @return Collection|Translation[]
     */
    public function filter(\Closure $predicate): Collection
    {
        return $this->collection->filter($predicate);
    }

    public function forAll(\Closure $predicate): bool
    {
        return $this->collection->forAll($predicate);
    }

    /**
     * @param \Closure $predicate
     * @return Collection|Translation[]
     */
    public function map(\Closure $callback): Collection
    {
        return $this->collection->map($callback);
    }

    /**
     * @param \Closure $predicate
     * @return Translation[]
     */
    public function partition(\Closure $predicate): array
    {
        return $this->collection->partition($predicate);
    }

    /**
     * @param Translation $translation
     * @return string|bool
     */
    public function indexOf($translation)
    {
        return $this->collection->indexOf($translation);
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return Translation[]
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function slice($offset, $length = null): array
    {
        return $this->collection->slice($offset, $length);
    }

    /**
     * @return \Traversable|Translation[]
     */
    public function getIterator(): \Traversable
    {
        return $this->collection->getIterator();
    }

    /**
     * @param string $locale
     * @return bool
     */
    public function offsetExists($locale): bool
    {
        return $this->collection->offsetExists($locale);
    }

    /**
     * @param string $locale
     * @return Translation|null
     */
    public function offsetGet($locale)
    {
        return $this->collection->offsetGet($locale);
    }

    /**
     * @param string $locale
     * @param Translation $translation
     */
    public function offsetSet($locale, $translation)
    {
        $this->set($locale, $translation);
    }

    /**
     * @param string $locale
     * @return Translation|null
     */
    public function offsetUnset($locale)
    {
        return $this->offsetUnset($locale);
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * @throws \LogicException
     */
    public function matching(Criteria $criteria): Collection
    {
        if (!$this->collection instanceof Selectable) {
            throw new \LogicException(sprintf(
                'Collection of class "%s" does not implement interface "%s" so you cannot call method "matching" on it.',
                get_class($this->collection),
                Selectable::class
            ));
        }

        return $this->collection->matching($criteria);
    }

    /**
     * @internal
     */
    public function unwrap(): Collection
    {
        return $this->collection;
    }
}
