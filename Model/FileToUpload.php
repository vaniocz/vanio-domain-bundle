<?php
namespace Vanio\DomainBundle\Model;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileToUpload extends UploadedFile
{
    /** @var bool */
    private $temporary = false;

    public function __construct(string $path, string $originalName = null, string $mimeType = null, int $size = null)
    {
        parent::__construct($path, $originalName ?? self::resolveOriginalName($path), $mimeType, $size, null, true);
    }

    public static function temporary(
        string $path,
        string $originalName = null,
        string $mimeType = null,
        int $size = null
    ): self {
        $filesystem = new Filesystem;
        $target = tempnam(sys_get_temp_dir(), '');
        $filesystem->copy($path, $target, true);
        $self = new self($target, $originalName, $mimeType, $size);
        $self->temporary = true;

        return $self;
    }

    public function move($directory, $name = null): SymfonyFile
    {
        if ($this->temporary) {
            return parent::move($directory, $name);
        }

        $target = $this->getTargetFile($directory, $name);
        $filesystem = new Filesystem;
        $filesystem->copy($this->getPathname(), $target);

        return $target;
    }

    private static function resolveOriginalName(string $path): string
    {
        return basename(preg_replace('~\?.*$~', '', $path));
    }
}
