<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

class SynchronizationListener implements EventSubscriber
{
    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $sourceEntityClass;

    /** @var string */
    private $targetEntityClass;

    /** @var array */
    private $sourceMapping;

    /** @var array */
    private $targetMapping;

    /** @var callable|null */
    private $sourceEntityFactory;

    /** @var callable|null */
    private $targetEntityFactory;

    /** @var callable|null */
    private $sourceEntityProvider;

    /** @var callable|null */
    private $targetEntityProvider;

    /** @var callable|null */
    private $sourceEntityHydrator;

    /** @var callable|null */
    private $targetEntityHydrator;

    /** @var callable|null */
    private $targetEntityRemover;

    public function __construct(string $sourceEntityClass, string $targetEntityClass, array $mapping)
    {
        $this->sourceEntityClass = $sourceEntityClass;
        $this->targetEntityClass = $targetEntityClass;
        $this->sourceMapping = $mapping;
        $this->targetMapping = array_reverse($mapping);
        $this->sourceEntityFactory = [$this, 'createTargetEntityBySourceEntity'];
        $this->targetEntityFactory = $this->sourceEntityFactory;
        $this->sourceEntityProvider = [$this, 'findTargetEntityBySourceEntity'];
        $this->targetEntityProvider = $this->sourceEntityProvider;
        $this->sourceEntityHydrator = [$this, 'updateTargetEntityBySourceEntity'];
        $this->targetEntityHydrator = $this->sourceEntityHydrator;
        $this->targetEntityRemover = [$this, 'removeTargetEntity'];
    }

    public function setSourceEntityFactory(callable $sourceEntityFactory = null): self
    {
        $this->sourceEntityFactory = $sourceEntityFactory;

        return $this;
    }

    public function setTargetEntityFactory(callable $targetEntityFactory = null): self
    {
        $this->targetEntityFactory = $targetEntityFactory;

        return $this;
    }

    public function setSourceEntityProvider(callable $sourceEntityProvider = null): self
    {
        $this->sourceEntityProvider = $sourceEntityProvider;

        return $this;
    }

    public function setTargetEntityProvider(callable $targetEntityProvider = null): self
    {
        $this->targetEntityProvider = $targetEntityProvider;

        return $this;
    }

    public function setSourceEntityHydrator(callable $sourceEntityHydrator = null): self
    {
        $this->sourceEntityHydrator = $sourceEntityHydrator;

        return $this;
    }

    public function setTargetEntityHydrator(callable $targetEntityHydrator = null): self
    {
        $this->targetEntityHydrator = $targetEntityHydrator;

        return $this;
    }

    public function setTargetEntityRemover(callable $targetEntityRemover = null): self
    {
        $this->targetEntityRemover = $targetEntityRemover;

        return $this;
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $this->entityManager = $event->getEntityManager();

        $this->synchronizeInsertedEntities();
        $this->synchronizeUpdatedEntities();
        $this->synchronizeDeletedEntities();
    }

    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    /**
     * @param object $sourceEntity
     * @internal
     */
    public function createTargetEntityBySourceEntity($sourceEntity)
    {
        $targetEntityClass = $this->resolveTargetEntityClass($sourceEntity);
        $targetEntity = new $targetEntityClass;

        $this->updateTargetEntityBySourceEntity($targetEntity, $sourceEntity);
    }

    /**
     * @param object $sourceEntity
     * @return object|null
     * @internal
     */
    public function findTargetEntityBySourceEntity($sourceEntity)
    {
        $entityMetadata = $this->getEntityMetadata($sourceEntity);
        $identifierValues = $entityMetadata->getIdentifierValues($sourceEntity);
        $mapping = $this->resolveMapping($sourceEntity);

        foreach ($identifierValues as $property => &$identifierValue) {
            $identifierValue = $this->normalizeIdentifierValue(
                $identifierValue,
                $entityMetadata->getTypeOfField($property)
            );

            if (($mapping[$property] ?? $property) !== $property) {
                unset($identifierValues[$property]);
            }
        }

        return $this->entityManager->find($this->resolveTargetEntityClass($sourceEntity), $identifierValues);
    }

    /**
     * @param object $targetEntity
     * @param object $sourceEntity
     * @internal
     */
    public function updateTargetEntityBySourceEntity($targetEntity, $sourceEntity)
    {
        $sourceMetadata = $this->getEntityMetadata($sourceEntity);
        $targetMetadata = $this->getEntityMetadata($targetEntity);

        foreach ($this->resolveMapping($sourceEntity) as $sourceProperty => $targetProperty) {
            $value = $sourceMetadata->getFieldValue($sourceEntity, $sourceProperty);
            $targetMetadata->setFieldValue($targetEntity, $targetProperty, $value);
        }
    }

    private function synchronizeInsertedEntities()
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $sourceEntity) {
            if ($this->isSourceOrTargetEntity($sourceEntity)) {
                if ($targetEntity = $this->createTargetEntity($sourceEntity)) {
                    $unitOfWork->persist($targetEntity);
                    $unitOfWork->computeChangeSet($this->getEntityMetadata($targetEntity), $targetEntity);
                }
            }
        }
    }

    private function synchronizeUpdatedEntities()
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $sourceEntity) {
            if ($this->isSourceOrTargetEntity($sourceEntity) && $this->needsSynchronization($sourceEntity)) {
                if ($targetEntity = $this->updateTargetEntity($sourceEntity)) {
                    $unitOfWork->recomputeSingleEntityChangeSet($this->getEntityMetadata($targetEntity), $targetEntity);
                }
            }
        }
    }

    private function synchronizeDeletedEntities()
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityDeletions() as $sourceEntity) {
            if ($this->isSourceOrTargetEntity($sourceEntity)) {
                $this->deleteTargetEntity($sourceEntity);
            }
        }
    }

    /**
     * @param object|bool $sourceEntity
     * @return object|null
     */
    private function createTargetEntity($sourceEntity)
    {
        $entityFactory = $this->resolveTargetEntityFactory($sourceEntity);

        return $entityFactory ? call_user_func($entityFactory, $sourceEntity) : null;
    }

    /**
     * @param object $sourceEntity
     * @return object|null
     */
    private function findTargetEntity($sourceEntity)
    {
        $entityProvider = $this->resolveTargetEntityProvider($sourceEntity);

        return $entityProvider ? call_user_func($entityProvider, $sourceEntity) : null;
    }

    /**
     * @param object $sourceEntity
     * @return object|null
     */
    private function updateTargetEntity($sourceEntity)
    {
        if (!$entityHydrator = $this->resolveTargetEntityHydrator($sourceEntity)) {
            return null;
        } elseif (!$targetEntity = $this->findTargetEntity($sourceEntity)) {
            return null;
        } else {
            call_user_func($entityHydrator, $targetEntity, $sourceEntity);
        }

        return $targetEntity;
    }

    /**
     * @param object $sourceEntity
     */
    private function deleteTargetEntity($sourceEntity)
    {
        if ($this->targetEntityRemover && $targetEntity = $this->findTargetEntityBySourceEntity($sourceEntity)) {
            call_user_func($this->targetEntityRemover, $targetEntity);
        }
    }

    /**
     * @param object $targetEntity
     */
    private function removeTargetEntity($targetEntity)
    {
        $this->entityManager->remove($targetEntity);
    }

    /**
     * @param object $entity
     * @return bool
     */
    private function isSourceOrTargetEntity($entity): bool
    {
        return $entity instanceof $this->sourceEntityClass || $entity instanceof $this->targetEntityClass;
    }

    /**
     * @param object $entity
     * @return bool
     */
    private function needsSynchronization($entity): bool
    {
        $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($entity);

        foreach ($this->resolveMapping($entity) as $sourceProperty => $targetProperty) {
            if (isset($changeSet[$sourceProperty])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object $entity
     * @return array
     */
    private function resolveMapping($entity): array
    {
        return $entity instanceof $this->sourceEntityClass ? $this->sourceMapping : $this->targetMapping;
    }

    /**
     * @param object $sourceEntity
     * @return string
     */
    private function resolveTargetEntityClass($sourceEntity): string
    {
        return $sourceEntity instanceof $this->sourceEntityClass
            ? $this->targetEntityClass
            : $this->sourceEntityClass;
    }

    /**
     * @param object $sourceEntity
     * @return callable|null
     */
    private function resolveTargetEntityFactory($sourceEntity)
    {
        return $sourceEntity instanceof $this->sourceEntityClass
            ? $this->targetEntityFactory
            : $this->sourceEntityFactory;
    }

    /**
     * @param object $sourceEntity
     * @return callable|null
     */
    private function resolveTargetEntityProvider($sourceEntity)
    {
        return $sourceEntity instanceof $this->sourceEntityClass
            ? $this->targetEntityProvider
            : $this->sourceEntityProvider;
    }

    /**
     * @param object $sourceEntity
     * @return callable|null
     */
    private function resolveTargetEntityHydrator($sourceEntity)
    {
        return $sourceEntity instanceof $this->sourceEntityClass
            ? $this->targetEntityHydrator
            : $this->sourceEntityHydrator;
    }

    /**
     * @param mixed $value
     * @param Type|string $type
     * @return mixed
     */
    private function normalizeIdentifierValue($value, $type)
    {
        if (!$type) {
            return $value;
        } elseif (is_string($type)) {
            $type = Type::getType($type);
        }

        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

        return $type->convertToDatabaseValue($value, $platform);
    }

    /**
     * @param object|string $entity
     * @return ClassMetadata
     */
    private function getEntityMetadata($entity): ClassMetadata
    {
        return $this->entityManager->getClassMetadata(is_string($entity) ? $entity : get_class($entity));
    }
}
