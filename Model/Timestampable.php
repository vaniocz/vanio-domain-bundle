<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks
 */
trait Timestampable
{
    /**
     * @var \DateTimeImmutable
     * @ORM\Column
     */
    private $createdAt;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column
     */
    private $updatedAt;

    /**
     * @ORM\PrePersist
     * @internal
     */
    public function stampOnCreate(): void
    {
        $this->createdAt = new \DateTimeImmutable;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @internal
     */
    public function stampOnUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable;
    }
}
