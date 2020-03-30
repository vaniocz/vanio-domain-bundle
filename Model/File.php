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
    public const OOXML_MIME_TYPES = [
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
    ];

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

    /**
     * @return mixed[]
     */
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
    public function setFile(?SymfonyFile $file): void
    {
        $this->file = $file;
        $this->uploadedAt = $this->uploadedAt ?? new \DateTimeImmutable;
    }

    public function fileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * @internal
     */
    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

    protected function loadMetadata(): void
    {
        if (!isset($this->metaData['name'])) {
            $name = $this->file instanceof SymfonyUploadedFile
                ? $this->file->getClientOriginalName()
                : $this->file->getBasename();
            $this->metaData['name'] = $name;
        }

        if (!isset($this->metaData['mimeType'])) {
            $this->metaData['mimeType'] = $this->guessMimeType(pathinfo($this->metaData['name'], PATHINFO_EXTENSION));
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

    private function guessMimeType(string $extension): string
    {
        $mimeType = MimeTypeGuesser::getInstance()->guess($this->file->getPathname());

        if (
            $mimeType !== 'application/octet-stream'
            || (new \finfo)->file($this->file->getPathname()) !== 'Microsoft OOXML'
        ) {
            return $mimeType;
        } elseif (
            $this->file instanceof SymfonyUploadedFile
            && Strings::startsWith($this->file->getClientMimeType(), 'application/vnd.openxmlformats-officedocument.')
        ) {
            return $this->file->getClientMimeType();
        }

        return self::OOXML_MIME_TYPES[$extension] ?? $mimeType;
    }
}
