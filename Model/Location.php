<?php
namespace Vanio\DomainBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Vanio\DomainBundle\Assert\Validate;

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
        $this->latitude = (float) $latitude;
        $this->longitude = (float) $longitude;

        Validate::lazy()
            ->that($this->address, 'address')
                ->notBlank('Address must not be blank.')
                ->maxLength(255, 'Address must not be longer than {{ max_length }} characters.')
            ->that($latitude, 'latitude')
                ->notBlank('Latitude must not be blank.')
                ->range(-90, 90, 'Latitude must be between {{ min }} and {{ max }} degrees.')
            ->that($longitude, 'longitude')
                ->notBlank('Longitude must not be blank.')
                ->range(-180, 180, 'Longitude must be between {{ min }} and {{ max }} degrees.')
            ->verifyNow();
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
