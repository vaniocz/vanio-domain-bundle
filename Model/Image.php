<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Vanio\DomainBundle\Assert\Validation;

/**
 * @ORM\Embeddable
 */
class Image extends File
{
    /**
     * @param SymfonyFile|File|string $file
     */
    public function __construct($file)
    {
        Validation::notBlank($file, 'Image must not be blank.');
        parent::__construct($file);
        Validation::supportedImageFile($this->file, 'Unknown image format.');
    }

    public function isImage(): bool
    {
        return true;
    }
}
