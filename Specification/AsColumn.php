<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\AbstractQuery;
use Happyr\DoctrineSpecification\Result\ResultModifier;

class AsColumn implements ResultModifier
{
    public function modify(AbstractQuery $query): void
    {
        $query->setHydrationMode('column');
    }
}
