<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Vanio\DomainBundle\Assert\Validation;

/**
 * @ORM\Embeddable
 */
class Location
{
    /**
     * @var string
     * @ORM\Column
     */
    private $address;

    /**
     * @var float
     * @ORM\Column
     */
    private $latitude;

    /**
     * @var float
     * @ORM\Column
     */
    private $longitude;

    public function __construct($address, $latitude, $longitude)
    {
        $this->address = trim($address);
        Validation::notBlank($this->address, 'Address must not be blank.');
        Validation::maxLength($this->address, 255, 'Address must not be longer than {{ max_length }} characters.');
        $this->latitude = (float) $latitude;
        Validation::range($this->latitude, -90, 90, 'Latitude must be between {{ min }} and {{ max }} degrees.');
        $this->longitude = (float) $longitude;
        Validation::range($this->longitude, -180, 180, 'Longitude must be between {{ min }} and {{ max }} degrees.');
    }

    public function address(): string
    {
        return $this->address;
    }

    public function multiLineAddress(): string
    {
        return str_replace(', ', "\n", $this->address);
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }
}
