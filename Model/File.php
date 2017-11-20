<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Vanio\DomainBundle\Assert\Validation;

/**
 * @ORM\Embeddable
 */
class File
{
    /**
     * @var SymfonyFile
     */
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

    /** @var bool */
    private $isImage = false;

    /**
     * @param SymfonyFile|self|string $file
     * @throws \InvalidArgumentException
     */
    public function __construct($file)
    {
        Validation::notBlank($file, 'File must not be blank.');

        if (!$file instanceof SymfonyFile && !$file instanceof self && !is_string($file)) {
            throw new \InvalidArgumentException(sprintf(
                'The file must be an instance of "%s" or a string.',
                SymfonyFile::class
            ));
        }

        if ($file instanceof self) {
            $this->metaData = $file->metaData;
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
        return $this->isImage;
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
     * @internal
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
        if ($this->metaData) {
            return;
        } elseif ($metadata = getimagesize($this->file)) {
            $this->isImage = true;
            $this->metaData = [
                'width' => $metadata[0],
                'height' => $metadata[1],
                'mimeType' => $metadata['mime'],
            ];
        }

        $name = $this->file instanceof SymfonyUploadedFile
            ? $this->file->getClientOriginalName()
            : $this->file->getBasename();
        $this->metaData += [
            'name' => $name,
            'size' => $this->file->getSize(),
        ];

        if (!isset($this->metaData['mimeType'])) {
            $this->metaData['mimeType'] = MimeTypeGuesser::getInstance()->guess($this->file->getPathname());
        }
    }
}
