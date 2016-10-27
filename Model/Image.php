<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;

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
        $this->loadMetadata();
    }

    public function metaData(): array
    {
        return $this->metaData;
    }

    private function loadMetadata()
    {
        $metadata = @getimagesize($this->file);

        if (!$metadata[0]) {
            throw new \InvalidArgumentException(sprintf('Unknown image format of file "%s".', $this->file));
        }

        $this->metaData = [
            'width' => $metadata[0],
            'height' => $metadata[1],
            'mime' => $metadata['mime'],
        ];
    }
}
