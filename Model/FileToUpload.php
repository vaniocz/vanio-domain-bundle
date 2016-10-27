<?php
namespace Vanio\DomainBundle\Model;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileToUpload extends UploadedFile
{
    public function __construct(string $path)
    {
        parent::__construct($path, basename($path), null, null, null, true);
    }

    public static function temporaryCopy(string $path): self
    {
        $filesystem = new Filesystem;
        $target = tempnam(sys_get_temp_dir(), '');
        $filesystem->copy($path, $target, true);

        return new self($target);
    }
}
