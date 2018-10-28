<?php
namespace Vanio\DomainBundle\Mapping;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Vanio\Stdlib\Strings;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class EmbeddedPropertyMapping extends PropertyMapping
{
    /** @var string */
    private $filePropertyName;

    public function __construct(
        string $filePropertyPath,
        string $embeddedFilePropertyPath = 'file',
        string $embeddedFileNamePropertyPath = 'fileName'
    ) {
        parent::__construct(
            "$filePropertyPath.$embeddedFilePropertyPath",
            "$filePropertyPath.$embeddedFileNamePropertyPath"
        );
        $this->filePropertyName = $filePropertyPath;
    }

    /**
     * @param object|array $object
     * @return File|null
     */
    public function getFile($object): ?File
    {
        try {
            $file = parent::getFile($object);
        } catch (UnexpectedTypeException $e) {
            return null;
        }

        return $file;
    }

    /**
     * @param object|array $object
     * @return string|null
     */
    public function getFileName($object): ?string
    {
        try {
            $fileName = parent::getFileName($object);
        } catch (UnexpectedTypeException $e) {
            return null;
        }

        return $fileName;
    }

    public function getFilePropertyName(): string
    {
        return $this->filePropertyName;
    }


    /**
     * @param object|array $object
     * @param string $propertyPath
     * @return string
     */
    protected function fixPropertyPath($object, $propertyPath): string
    {
        if (!is_array($object) || Strings::startsWith($propertyPath, '[')) {
            return $propertyPath;
        }

        $properties = explode('.', $propertyPath);
        $properties[0] = sprintf('[%s]', $properties[0]);

        return implode('.', $properties);
    }
}
