<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

class ColumnHydrator extends AbstractHydrator
{
    protected function hydrateAllData()
    {
        return $this->_stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
