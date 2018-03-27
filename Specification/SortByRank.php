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

    /** @var string|null */
    private $dqlAlias;

    /**
     * @param string $searchTerm
     * @param string $searchDocumentField
     */
    public function __construct(
        string $searchTerm,
        string $searchDocumentField = 'searchDocument',
        string $dqlAlias = null
    ) {
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
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        $queryBuilder
            ->addOrderBy(sprintf('tsrank(%s.%s, :_rankQuery)', $dqlAlias, $this->searchDocumentField), 'DESC')
            ->setParameter('_rankQuery', $this->processSearchTerm($this->searchTerm));
    }
}
