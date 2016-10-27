<?php
namespace Vanio\DomainBundle\Mapping;

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

    public function getFilePropertyName(): string
    {
        return $this->filePropertyName;
    }
}
