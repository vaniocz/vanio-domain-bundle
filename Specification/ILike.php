<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Like;
use Happyr\DoctrineSpecification\ValueConverter;

class ILike extends Like
{
    /**
     * @param QueryBuilder $qb
     * @param string|null $dqlAlias
     * @return string
     */
    public function getFilter(QueryBuilder $qb, $dqlAlias)
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        $paramName = $this->getParameterName($qb);
        $qb->setParameter($paramName, ValueConverter::convertToDatabaseValue($this->value, $qb));

        return (string) new Comparison(
            sprintf('LOWER(%s.%s)', $dqlAlias, $this->field),
            'LIKE',
            sprintf(':%s', mb_strtolower($paramName))
        );
    }
}
