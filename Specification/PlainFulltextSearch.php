<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;
use Vanio\DomainBundle\Doctrine\QueryBuilderUtility;

class PlainFulltextSearch implements Filter
{
    /** @var string */
    private $searchTerm;

    /** @var string */
    private $searchDocumentField;

    /** @var string|null */
    private $dqlAlias;

    public function __construct(
        string $searchTerm,
        string $searchDocumentField = 'fulltextDocument',
        string $dqlAlias = null
    ) {
        $this->searchTerm = $searchTerm;
        $this->searchDocumentField = $searchDocumentField;
        $this->dqlAlias = $dqlAlias;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $dqlAlias
     * @return string
     */
    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias): string
    {
        $parameter = QueryBuilderUtility::generateUniqueDqlAlias(__CLASS__);
        $queryBuilder->setParameter($parameter, $this->searchTerm);

        return sprintf(
            'plain_tsquery(%s.%s, :%s) = true',
            $this->dqlAlias ?? $dqlAlias,
            $this->searchDocumentField,
            $parameter
        );
    }
}
