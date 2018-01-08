<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Happyr\DoctrineSpecification\Result\ResultModifier;

class ResultModifierChain implements ResultModifier
{
    /** @var ResultModifier[] */
    private $resultModifiers;

    /**
     * @param ResultModifier[] $resultModifiers
     */
    public function __construct($resultModifiers)
    {
        $this->resultModifiers = $resultModifiers;
    }

    public function modify(AbstractQuery $query)
    {
        foreach ($this->resultModifiers as $resultModifier) {
            $resultModifier->modify($query);
        }
    }
}
