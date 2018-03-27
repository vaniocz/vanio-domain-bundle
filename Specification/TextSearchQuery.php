<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;

class TextSearchQuery extends TextSearchSpecification implements Filter
{
    /** @var string */
    private $searchTerm;

    /** @var string */
    private $searchDocumentField;

    /** @var string|null */
    private $dqlAlias;

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
     * @return string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias): string
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        $queryBuilder->setParameter('_searchQuery', $this->processSearchTerm($this->searchTerm));

        return sprintf('tsquery(%s.%s, :_searchQuery) = true', $dqlAlias, $this->searchDocumentField);
    }
}
