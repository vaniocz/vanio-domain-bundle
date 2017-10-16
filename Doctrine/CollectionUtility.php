<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;

class CollectionUtility
{
    /**
     * @param Collection $collection
     * @param \Traversable|array $elements
     */
    public static function replace(Collection $collection, $elements)
    {
        if ($collection instanceof PersistentCollection) {
            self::replacePersistentCollection($collection, $elements);
            $collection = $collection->unwrap();
        }

        $collection->clear();

        foreach ($elements as $key => $element) {
            $collection[$key] = $element;
        }
    }

    /**
     * @param PersistentCollection $collection
     * @param \Traversable|object[] $entities
     */
    private static function replacePersistentCollection(PersistentCollection $collection, $entities)
    {
        $actualEntities = new \SplObjectStorage;
        $pastEntities = new \SplObjectStorage;

        foreach ($entities as $entity) {
            $actualEntities[$entity] = true;
        }

        foreach ($collection as $key => $entity) {
            $pastEntities[$entity] = true;

            if (!isset($actualEntities[$entity])) {
                unset($collection[$key]);
            }
        }

        foreach ($actualEntities as $entity) {
            if (!isset($pastEntities[$entity])) {
                $collection[] = $entity;
                break;
            }
        }
    }
}
