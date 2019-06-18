<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Like;
use Happyr\DoctrineSpecification\ValueConverter;

class ILike extends Like
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $dqlAlias
     * @return string
     */
    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias): string
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        $parameter = $this->getParameterName($queryBuilder);
        $value = mb_strtolower(ValueConverter::convertToDatabaseValue($this->value, $queryBuilder));
        $value = str_replace(['\\', '%'], ['\\\\', '\\%'], $value);
        $queryBuilder->setParameter($parameter, $value);

        return (string) new Comparison(
            sprintf('LOWER(%s.%s)', $dqlAlias, $this->field),
            'LIKE',
            sprintf(':%s', $parameter)
        );
    }
}
