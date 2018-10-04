<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;

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
     * @internal
     * @ORM\PrePersist
     */
    public function stampOnCreate(): void
    {
        $this->createdAt = new \DateTimeImmutable;
    }

    /**
     * @internal
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function stampOnUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable;
    }
}
