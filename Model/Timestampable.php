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

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

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
