<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\Common\Collections\Collection;

interface CollectionDecorator extends Collection
{
    /**
     * @internal
     */
    public function unwrap(): Collection;
}
