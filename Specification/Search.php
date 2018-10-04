<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\ValueConverter;

class Search implements Filter
{
    /** @var string[] */
    private $properties;

    /** @var string */
    private $term;

    /** @var string|null */
    private $dqlAlias;

    /**
     * @param string|string[] $property
     * @param string $term
     * @param string|bool|null $dqlAlias
     */
    public function __construct($property, string $term, $dqlAlias = null)
    {
        $this->properties = (array) $property;
        $this->term = trim($term);
        $this->dqlAlias = $dqlAlias;
    }

    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias): string
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        $and = [];

        foreach (preg_split('/[\s,]+/', trim($this->term)) as $term) {
            $or = [];

            foreach ($this->properties as $property) {
                $parameter = sprintf('_term_%d', count($queryBuilder->getParameters()));
                $value = mb_strtolower(ValueConverter::convertToDatabaseValue($term, $queryBuilder));
                $queryBuilder->setParameter($parameter, "%{$value}%");
                $or[] = sprintf(
                    'LOWER(UNACCENT(%s)) LIKE UNACCENT(:%s)',
                    $dqlAlias === false ? $property : "{$dqlAlias}.{$property}",
                    $parameter
                );
            }

            $and[] = sprintf('(%s)', implode(' OR ', $or));
        }

        return implode(' AND ', $and);
    }
}
