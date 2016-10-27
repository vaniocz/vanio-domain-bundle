<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Vanio\DomainBundle\Assert\Validation;

/**
 * @ORM\Embeddable
 */
class Image extends File
{
    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    private $metaData;

    public function __construct($file)
    {
        parent::__construct($file);
        Validation::supportedImageFile($this->file, 'Unknown photo format.');
        $metadata = getimagesize($this->file);
        $this->metaData = [
            'width' => $metadata[0],
            'height' => $metadata[1],
            'mime' => $metadata['mime'],
        ];
    }

    public function metaData(): array
    {
        return $this->metaData;
    }
}
