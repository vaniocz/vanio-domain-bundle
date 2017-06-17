<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;

class SortByRank extends TextSearchSpecification implements QueryModifier
{
    /** @var string */
    private $searchTerm;

    /** @var string */
    private $searchDocumentField;

    /**
     * @param string $searchTerm
     * @param string $searchDocumentField
     */
    public function __construct(string $searchTerm, string $searchDocumentField = 'searchDocument')
    {
        $this->searchTerm = $searchTerm;
        $this->searchDocumentField = $searchDocumentField;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $dqlAlias
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias)
    {
        $queryBuilder
            ->addOrderBy(sprintf('tsrank(%s.%s, :_rankQuery)', $dqlAlias, $this->searchDocumentField), 'DESC')
            ->setParameter('_rankQuery', $this->processSearchTerm($this->searchTerm));
    }
}
