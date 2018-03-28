<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Logic\AndX;
use Happyr\DoctrineSpecification\Logic\LogicX;
use Happyr\DoctrineSpecification\Result\ResultModifier;
use Vanio\Stdlib\Objects;

class ResultModifierChain implements ResultModifier
{
    /** @var AndX */
    private $specification;

    public function __construct(AndX $specification = null)
    {
        $this->specification = $specification ?: new AndX;
    }

    public function append($specification)
    {
        $this->specification->andX($specification);
    }

    public function modify(AbstractQuery $query)
    {
        foreach ($this->resolveResultModifiers($this->specification) as $resultModifier) {
            $resultModifier->modify($query);
        }
    }

    /**
     * @param mixed $specification
     * @return ResultModifier[]
     */
    private function resolveResultModifiers($specification): array
    {
        if ($specification instanceof ResultModifier) {
            return [$specification];
        } elseif ($specification instanceof Specification) {
            /** @noinspection PhpInternalEntityUsedInspection */
            return $this->resolveResultModifiers($specification->buildSpecification('e'));
        } elseif ($specification instanceof BaseSpecification) {
            return $this->resolveResultModifiers($this->getBaseSpecificationSpec($specification));
        }

        $resultModifiers = [];

        if ($specification instanceof LogicX) {
            foreach (Objects::getPropertyValue($specification, 'children', LogicX::class) as $child) {
                $resultModifiers = array_merge($resultModifiers, $this->resolveResultModifiers($child));
            }
        }

        return $resultModifiers;
    }

    /**
     * @return mixed
     */
    private function getBaseSpecificationSpec(BaseSpecification $specification)
    {
        $getSpec = function () {
            return $this->{'getSpec'}();
        };
        $getSpec = $getSpec->bindTo($specification, BaseSpecification::class);

        return $getSpec();
    }
}
