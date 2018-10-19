<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\Stdlib\Strings;

/**
 * @ORM\Embeddable
 */
class File
{
    /** @var SymfonyFile */
    protected $file;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    protected $uploadedAt;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    protected $fileName;

    /**
     * @var array
     * @ORM\Column(type="json")
     */
    protected $metaData = [];

    /**
     * @param SymfonyFile|self|string $file
     * @param mixed[] $metaData
     * @throws \InvalidArgumentException
     */
    public function __construct($file, array $metaData = [])
    {
        Validation::notBlank($file, 'File must not be blank.');

        if (!$file instanceof SymfonyFile && !$file instanceof self && !is_string($file)) {
            throw new \InvalidArgumentException(sprintf(
                'The file must be an instance of "%s" or a string.',
                SymfonyFile::class
            ));
        }

        $this->metaData = $metaData;

        if ($file instanceof self) {
            $this->metaData += $file->metaData;
            $file = $file->file();
        }

        $this->setFile($file instanceof SymfonyUploadedFile ? $file : new FileToUpload($file));
        $this->loadMetadata();
    }

    public function file(): SymfonyFile
    {
        return $this->file;
    }

    public function uploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function metaData(): array
    {
        return $this->metaData;
    }

    public function isImage(): bool
    {
        return $this->metaData['isImage'];
    }

    /**
     * @internal
     */
    public function setFile(SymfonyFile $file = null)
    {
        $this->file = $file;
        $this->uploadedAt = new \DateTimeImmutable;
    }

    /**
     * @return string|null
     */
    public function fileName()
    {
        return $this->fileName;
    }

    /**
     * @internal
     */
    public function setFileName(string $fileName = null)
    {
        $this->fileName = $fileName;
    }

    protected function loadMetadata()
    {
        if (!isset($this->metaData['name'])) {
            $name = $this->file instanceof SymfonyUploadedFile
                ? $this->file->getClientOriginalName()
                : $this->file->getBasename();
            $this->metaData['name'] = $name;
        }

        if (!isset($this->metaData['mimeType'])) {
            $this->metaData['mimeType'] = MimeTypeGuesser::getInstance()->guess($this->file->getPathname());
        }

        if (!isset($this->metaData['format'])) {
            $this->metaData['format'] = ExtensionGuesser::getInstance()->guess($this->metaData['mimeType']);
        }

        if (!isset($this->metaData['size'])) {
            $this->metaData['size'] = $this->file->getSize();
        }

        if (!isset($this->metaData['isImage'])) {
            $this->metaData['isImage'] = false;
        }

        if (Strings::startsWith($this->metaData['mimeType'], 'image/')) {
            if ($this->metaData['mimeType'] === 'image/svg+xml') {
                $this->metaData['isImage'] = true;
            } elseif ($metaData = @getimagesize($this->file)) {
                $this->metaData['mimeType'] = $metaData['mime'];
                $this->metaData['width'] = $metaData[0];
                $this->metaData['height'] = $metaData[1];
                $this->metaData['isImage'] = true;
            }
        }
    }
}
