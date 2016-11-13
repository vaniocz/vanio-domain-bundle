<?php
namespace Vanio\DomainBundle\Mapping;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class EmbeddedPropertyMapping extends PropertyMapping
{
    /** @var string */
    private $filePropertyName;

    public function __construct(
        string $filePropertyPath,
        string $embeddedFilePropertyPath = 'file',
        string $embeddedFileNamePropertyPath = 'file_name'
    ) {
        parent::__construct(
            "$filePropertyPath.$embeddedFilePropertyPath",
            "$filePropertyPath.$embeddedFileNamePropertyPath"
        );
        $this->filePropertyName = $filePropertyPath;
    }

    /**
     * @param object $object
     * @return File|null
     */
    public function getFile($object)
    {
        try {
            $file = parent::getFile($object);
        } catch (UnexpectedTypeException $e) {
            return null;
        }

        return $file;
    }

    /**
     * @param object $object
     * @return string|null
     */
    public function getFileName($object)
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
}
