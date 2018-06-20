<?php
namespace Vanio\DomainBundle\Mapping;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class EmbeddedPropertyMappingFactory extends PropertyMappingFactory
{
    /**
     * @param object|array $object
     * @param string $field
     * @param string|null $class
     * @return PropertyMapping|EmbeddedPropertyMapping|null
     */
    public function fromField($object, $field, $class = null)
    {
        if (!$mapping = parent::fromField($object, $field, $class)) {
            return null;
        }

        $class = $this->getClassName($object, $class);
        $entityManager = $this->doctrine()->getManagerForClass($class);

        try {
            if (isset($entityManager->getClassMetadata($class)->embeddedClasses[$field])) {
                return $this->createEmbeddedMapping($mapping, $this->metadata->getUploadableField($class, $field));
            }
        } catch (MappingException $e) {}

        return $mapping;
    }

    /**
     * @param PropertyMapping $mapping
     * @param array $mappingData
     * @return EmbeddedPropertyMapping
     */
    private function createEmbeddedMapping(PropertyMapping $mapping, array $mappingData): EmbeddedPropertyMapping
    {
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
