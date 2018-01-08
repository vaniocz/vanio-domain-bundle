<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\AbstractQuery;

interface ResultTransformer
{
    public function transformResult(AbstractQuery $query, $result);
}
