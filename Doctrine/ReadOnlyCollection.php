<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;

class ReadOnlyCollection implements Collection, Selectable
{
    /** @var Collection */
    private $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param mixed $element
     * @throws \LogicException
     */
    public function add($element)
    {
        $this->invalidAccess('add an element to');
    }

    /**
     * @throws \LogicException
     */
    public function clear()
    {
        $this->invalidAccess('clear');
    }

    public function contains($element): bool
    {
        return $this->collection->contains($element);
    }

    public function isEmpty(): bool
    {
        return $this->collection->isEmpty();
    }

    /**
     * @param string|int $key
     * @throws \LogicException
     */
    public function remove($key)
    {
        $this->invalidAccess('remove from');
    }

    /**
     * @param mixed $element
     * @throws \LogicException
     */
    public function removeElement($element)
    {
        $this->invalidAccess('remove an element from');
    }

    /**
     * @param string|int $key
     * @return bool
     */
    public function containsKey($key): bool
    {
        return $this->collection->containsKey($key);
    }

    /**
     * @param string|int $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->collection->get($key);
    }

    public function getKeys(): array
    {
        return $this->collection->getKeys();
    }

    public function getValues(): array
    {
        return $this->collection->getValues();
    }

    /**
     * @param string|int $key
     * @param mixed $value
     * @throws \LogicException
     */
    public function set($key, $value)
    {
        $this->invalidAccess('set an element in');
    }

    public function toArray(): array
    {
        return $this->collection->toArray();
    }

    public function first()
    {
        return $this->collection->first();
    }

    public function last()
    {
        return $this->collection->last();
    }

    /**
     * @return string|int
     */
    public function key()
    {
        return $this->collection->key();
    }

    public function current()
    {
        return $this->collection->current();
    }

    public function next()
    {
        return $this->collection->next();
    }

    public function exists(\Closure $predicate): bool
    {
        return $this->collection->exists($predicate);
    }

    public function filter(\Closure $predicate): Collection
    {
        return $this->collection->filter($predicate);
    }

    public function forAll(\Closure $predicate): bool
    {
        return $this->collection->forAll($predicate);
    }

    public function map(\Closure $callback): Collection
    {
        return $this->collection->map($callback);
    }

    public function partition(\Closure $predicate): array
    {
        return $this->collection->partition($predicate);
    }

    /**
     * @param mixed $element
     * @return string|int|bool
     */
    public function indexOf($element)
    {
        return $this->collection->indexOf($element);
    }

    /**
     * @param int $offset
     * @param int|null $length
     */
    public function slice($offset, $length = null)
    {
        return $this->collection->slice($offset, $length);
    }

    public function getIterator(): \Traversable
    {
        return $this->collection->getIterator();
    }

    public function offsetExists($offset): bool
    {
        return $this->collection->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->collection->offsetGet($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \LogicException
     */
    public function offsetSet($offset, $value)
    {
        $this->invalidAccess('set an element in');
    }

    /**
     * @param mixed $offset
     * @throws \LogicException
     */
    public function offsetUnset($offset)
    {
        $this->invalidAccess('remove from');
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function matching(Criteria $criteria): Collection
    {
        if (!$this->collection instanceof Selectable) {
            throw new \LogicException(sprintf(
                'Collection %s does not implement "%s" so you cannot call ->matching() on it.',
                get_class($this->collection),
                Selectable::class
            ));
        }

        return $this->collection->matching($criteria);
    }

    /**
     * @param string $action
     * @throws \LogicException
     */
    private function invalidAccess(string $action)
    {
        throw new \LogicException("Cannot $action read-only collection, write/modify operations are forbidden.");
    }
}
