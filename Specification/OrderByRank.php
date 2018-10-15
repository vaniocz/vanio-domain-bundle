<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Doctrine\QueryBuilderUtility;

class OrderByRank implements QueryModifier
{
    /** @var string */
    private $searchTerm;

    /** @var string */
    private $searchDocumentField;

    /** @var string|null */
    private $dqlAlias;

    /**
     * @param string $searchTerm
     * @param string $searchDocumentField
     */
    public function __construct(
        string $searchTerm,
        string $searchDocumentField = 'fulltextDocument',
        string $dqlAlias = null
    )
    {
        $this->searchTerm = $searchTerm;
        $this->searchDocumentField = $searchDocumentField;
        $this->dqlAlias = $dqlAlias;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $dqlAlias
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        $parameter = QueryBuilderUtility::generateUniqueDqlAlias(__CLASS__);
        $sort = sprintf(
            'tsrank(%s.%s, :%s)',
            $this->dqlAlias ?? $dqlAlias,
            $this->searchDocumentField, $parameter
        );
        $queryBuilder
            ->addOrderBy($sort, 'DESC')
            ->setParameter($parameter, FulltextSearch::processSearchTerm($this->searchTerm));
    }
}
