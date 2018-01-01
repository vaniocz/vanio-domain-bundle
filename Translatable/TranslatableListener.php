<?php
namespace Vanio\DomainBundle\Translatable;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

class TranslatableListener implements EventSubscriber
{
    /** @var callable|null */
    private $currentLocaleCallable;

    /** @var callable|null */
    private $defaultLocaleCallable;

    /** @param string|int|null */
    private $translatableFetchMode;

    /** @param string|int|null */
    private $translationFetchMode;

    /**
     * @param callable|null $currentLocaleCallable
     * @param callable|null $defaultLocaleCallable
     * @param string|int|null $translatableFetchMode
     * @param string|int|null $translationFetchMode
     */
    public function __construct(
        callable $currentLocaleCallable = null,
        callable $defaultLocaleCallable = null,
        $translatableFetchMode = null,
        $translationFetchMode = null
    ) {
        $this->currentLocaleCallable = $currentLocaleCallable;
        $this->defaultLocaleCallable = $defaultLocaleCallable;
        $this->translatableFetchMode = $this->normalizeFetchMode($translatableFetchMode);
        $this->translationFetchMode = $this->normalizeFetchMode($translationFetchMode);
    }

    /**
     * @return string|null
     */
    public function resolveCurrentLocale()
    {
        $currentLocaleCallable = $this->currentLocaleCallable;

        return $currentLocaleCallable ? $currentLocaleCallable() : null;
    }

    /**
     * @return string|null
     */
    public function resolveDefaultLocale()
    {
        $defaultLocaleCallable = $this->defaultLocaleCallable;

        return $defaultLocaleCallable ? $defaultLocaleCallable() : null;
    }

    public function getSubscribedEvents(): array
    {
        return [Events::loadClassMetadata, Events::postLoad, Events::prePersist];
    }

    /**
     * @internal
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();

        if (!$metadata->reflClass) {
            return;
        } elseif (is_a($metadata->name, Translatable::class, true)) {
            $this->mapTranslatable($metadata);
        } elseif (is_a($metadata->name, Translation::class, true)) {
            $this->mapTranslation($metadata, $event->getEntityManager());
        }
    }

    /**
     * @internal
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $this->injectLocales($event);
    }

    /**
     * @internal
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $this->injectLocales($event);
    }

    private function mapTranslatable(ClassMetadata $metadata)
    {
        if (!$metadata->hasAssociation('translations')) {
            $metadata->mapOneToMany([
                'fieldName' => 'translations',
                'targetEntity' => $metadata->reflClass->getMethod('translationClass')->invoke(null),
                'mappedBy' => 'translatable',
                'indexBy' => 'locale',
                'cascade' => ['persist', 'merge', 'remove'],
                'fetch' => $metadata->reflClass->hasMethod('translatableFetchMode')
                    ? $metadata->reflClass->getMethod('translatableFetchMode')->invoke(null)
                    : $this->translatableFetchMode,
                'orphanRemoval' => true,
            ]);
        }
    }

    private function mapTranslation(ClassMetadata $metadata, EntityManager $entityManager)
    {
        $this->mapId($metadata, $entityManager);

        if (!$metadata->hasAssociation('translatable')) {
            $metadata->mapManyToOne([
                'fieldName' => 'translatable',
                'targetEntity' => $metadata->getReflectionClass()->getMethod('translatableClass')->invoke(null),
                'inversedBy' => 'translations',
                'cascade' => ['persist', 'merge'],
                'fetch' => $metadata->reflClass->hasMethod('translationFetchMode')
                    ? $metadata->reflClass->getMethod('translationFetchMode')->invoke(null)
                    : $this->translationFetchMode,
                'joinColumns' => [['onDelete' => 'CASCADE']],
            ]);
        }

        $uniqueConstraintName = sprintf('%s_unique_translation', $metadata->getTableName());

        if (!isset($metadata->table['uniqueConstraints'][$uniqueConstraintName])) {
            $metadata->table['uniqueConstraints'][$uniqueConstraintName] = [
                'columns' => ['translatable_id', 'locale'],
            ];
        }

        if (!$metadata->hasField('locale') && !$metadata->hasAssociation('locale')) {
            $metadata->mapField([
                'fieldName' => 'locale',
                'type' => 'string',
            ]);
        }
    }

    private function mapId(ClassMetadata $metadata, EntityManager $entityManager)
    {
        if ($metadata->getIdentifier()) {
            return;
        }

        (new ClassMetadataBuilder($metadata))
            ->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();
        $this->completeIdGeneratorMapping($entityManager->getMetadataFactory(), $metadata);
    }

    /**
     * @param string|int|null $fetchMode
     * @return int
     */
    private function normalizeFetchMode($fetchMode): int
    {
        switch (strtoupper($fetchMode)) {
            case ClassMetadata::FETCH_EAGER:
            case 'EAGER':
                return ClassMetadata::FETCH_EAGER;
            case ClassMetadata::FETCH_EXTRA_LAZY:
            case 'EXTRA_LAZY':
                return ClassMetadata::FETCH_EXTRA_LAZY;
            case ClassMetadata::FETCH_LAZY:
            case 'LAZY':
            case null:
                return ClassMetadata::FETCH_LAZY;
        }

        throw new \InvalidArgumentException(sprintf('Invalid fetch mode "%s".', $fetchMode));
    }

    private function completeIdGeneratorMapping(ClassMetadataFactory $metadataFactory, ClassMetadata $metadata)
    {
        $completeIdGeneratorMapping = function () use ($metadata) {
            return $this->{'completeIdGeneratorMapping'}($metadata);
        };
        $completeIdGeneratorMapping = $completeIdGeneratorMapping->bindTo(
            $metadataFactory,
            ClassMetadataFactory::class
        );

        return $completeIdGeneratorMapping();
    }

    private function injectLocales(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        $translatableInterface = Translatable::class;

        if (!$entity instanceof $translatableInterface) {
            return;
        }

        if (is_callable([$entity, 'setCurrentLocale'])) {
            $locale = $this->resolveCurrentLocale();

            if ($locale !== null) {
                $entity->setCurrentLocale($locale);
            }
        }

        if (is_callable([$entity, 'setDefaultLocale'])) {
            $locale = $this->resolveDefaultLocale();

            if ($locale !== null) {
                $entity->setDefaultLocale($locale);
            }
        }
    }
}
