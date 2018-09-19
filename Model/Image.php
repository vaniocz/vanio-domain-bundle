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
     * @param mixed[] $metaData
     */
    public function __construct($file, array $metaData = [])
    {
        Validation::notBlank($file, 'Image must not be blank.');
        parent::__construct($file, $metaData);
        Validation::true(parent::isImage(), sprintf('Unknown image format of file "%s".', $this->metaData['name']));
    }

    public function isImage(): bool
    {
        return true;
    }
}
