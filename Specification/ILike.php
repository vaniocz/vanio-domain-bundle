<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\ValueConverter;

class ILike implements Filter
{
    const CONTAINS = '%%%s%%';
    const ENDS_WITH = '%%%s';
    const STARTS_WITH = '%s%%';

    /** @var string */
    private $property;

    /** @var string */
    private $value;

    /** @var string */
    private $format;

    /** @var string */
    private $dqlAlias;

    public function __construct(
        string $property,
        string $value,
        string $format = self::CONTAINS,
        ?string $dqlAlias = null
    ) {
        $this->property = $property;
        $this->value = $value;
        $this->format = $format;
        $this->dqlAlias = $dqlAlias;
    }

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

        $parameter = sprintf('i_like_%d', $queryBuilder->getParameters()->count());
        $value = str_replace(['\\', '%'], ['\\\\', '\\%'], $this->value);
        $value = sprintf($this->format, $value);
        $value = mb_strtolower(ValueConverter::convertToDatabaseValue($value, $queryBuilder));
        $queryBuilder->setParameter($parameter, $value);

        return "LOWER({$dqlAlias}.{$this->property}) LIKE :{$parameter}";
    }
}
