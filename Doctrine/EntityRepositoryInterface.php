<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Happyr\DoctrineSpecification\EntitySpecificationRepositoryInterface;

if (interface_exists(ServiceEntityRepositoryInterface::class)) {
    interface EntityRepositoryInterface extends EntitySpecificationRepositoryInterface, ServiceEntityRepositoryInterface
    {}
} else {
    interface EntityRepositoryInterface extends EntitySpecificationRepositoryInterface
    {}
}
