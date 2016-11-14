<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as FileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Embeddable
 */
class File
{
    /**
     * @var FileInfo
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
     * @ORM\Column(type="universal_json")
     */
    protected $metaData;

    /**
     * @param FileInfo|self|string $file
     * @throws \InvalidArgumentException
     */
    public function __construct($file)
    {
        if (!$file instanceof FileInfo && !$file instanceof self && !is_string($file)) {
            throw new \InvalidArgumentException(sprintf(
                'The file must be an instance of "%s" or a string.',
                FileInfo::class
            ));
        }

        if ($file instanceof self) {
            $this->metaData = $file->metaData;
            $file = $file->file();
        }

        $file = $file instanceof UploadedFile ? $file : FileToUpload::temporaryCopy($file);
        $this->setFile($file);
        $this->metaData = $this->metaData ?? [
            'name' => $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getBasename(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * @return FileInfo
     */
    public function file()
    {
        return $this->file;
    }

    public function uploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    /**
     * @internal
     */
    public function setFile(FileInfo $file = null)
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
}
