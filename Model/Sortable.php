<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait Sortable
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @Gedmo\SortablePosition
     */
    private $position;

    public function position(): int
    {
        return $this->position;
    }

    public function move(int $position): void
    {
        $this->position = $position;
    }
}
