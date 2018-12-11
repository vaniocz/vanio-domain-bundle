<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Range
{
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $minimum;

    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $maximum;

    public function __construct(float $minimum, float $maximum)
    {
        $this->minimum = $minimum;
        $this->maximum = $maximum;
    }

    public function minimum(): float
    {
        return $this->minimum;
    }

    public function maximum(): float
    {
        return $this->maximum;
    }
}
