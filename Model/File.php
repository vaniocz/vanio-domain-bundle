<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

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
     * @ORM\Column(type="universal_json")
     */
    protected $metaData;

    /**
     * @param SymfonyFile|self|string $file
     * @throws \InvalidArgumentException
     */
    public function __construct($file)
    {
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

        $file = $file instanceof SymfonyUploadedFile ? $file : FileToUpload::temporaryCopy($file);
        $this->setFile($file);

        if (!$this->metaData) {
            $this->metaData = [
                'name' => $file instanceof SymfonyUploadedFile ? $file->getClientOriginalName() : $file->getBasename(),
                'size' => $file->getSize(),
            ];
        }
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
}
