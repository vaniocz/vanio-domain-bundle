<?php
namespace Vanio\DomainBundle\Mapping;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class EmbeddedPropertyMappingFactory extends PropertyMappingFactory
{
    /** @var EntityManager */
    private $entityManager;

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function createMapping($object, $property, array $mappingData)
    {
        $class = ClassUtils::getClass($object);
        $mapping = parent::createMapping($object, $property, $mappingData);

        if (
            !$this->entityManager->getMetadataFactory()->hasMetadataFor($class)
            || !isset($this->entityManager->getClassMetadata($class)->embeddedClasses[$property])
        ) {
            return $mapping;
        }

        $embeddedMapping = new EmbeddedPropertyMapping($mapping->getFilePropertyName());
        $embeddedMapping->setMappingName($mapping->getMappingName());
        $embeddedMapping->setMapping($this->mappings[$mappingData['mapping']]);

        if ($namer = $mapping->getNamer()) {
            $embeddedMapping->setNamer($namer);
        }

        if ($directoryNamer = $mapping->getDirectoryNamer()) {
            $embeddedMapping->setDirectoryNamer($directoryNamer);
        }

        return $embeddedMapping;
    }
}
