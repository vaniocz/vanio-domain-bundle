<?php
namespace Vanio\DomainBundle\Mapping;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class EmbeddedPropertyMappingFactory extends PropertyMappingFactory
{
    /**
     * @param object $object
     * @param string $property
     * @param array $mappingData
     * @return PropertyMapping|EmbeddedPropertyMapping
     */
    protected function createMapping($object, $property, array $mappingData): PropertyMapping
    {
        $class = ClassUtils::getClass($object);
        $mapping = parent::createMapping($object, $property, $mappingData);
        $entityManager = $this->doctrine()->getManagerForClass($class);

        if (
            !$entityManager->getMetadataFactory()->hasMetadataFor($class)
            || !isset($entityManager->getClassMetadata($class)->embeddedClasses[$property])
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

    private function doctrine(): ManagerRegistry
    {
        return $this->container->get('doctrine');
    }
}
