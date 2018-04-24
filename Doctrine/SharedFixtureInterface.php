<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Common\DataFixtures\SharedFixtureInterface as BaseSharedFixtureInterface;

if (interface_exists(ORMFixtureInterface::class)) {
    interface SharedFixtureInterface extends BaseSharedFixtureInterface, ORMFixtureInterface
    {}
} else {
    interface SharedFixtureInterface extends BaseSharedFixtureInterface
    {}
}
