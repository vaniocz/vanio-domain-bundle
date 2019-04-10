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

    /** @var bool */
    private $isPlaintext = false;

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

    public static function plaintext(
        string $searchTerm,
        string $searchDocumentField = 'fulltextDocument',
        string $dqlAlias = null
    ): self {
        $self = new self($searchTerm, $searchDocumentField, $dqlAlias);
        $self->isPlaintext = true;

        return $self;
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
            '%s(%s.%s, :%s)',
            $this->isPlaintext ? : 'plain_tsrank', 'tsrank',
            $this->dqlAlias ?? $dqlAlias,
            $this->searchDocumentField,
            $parameter
        );
        $queryBuilder
            ->addOrderBy($sort, 'DESC')
            ->setParameter(
                $parameter,
                $this->isPlaintext ? $this->searchTerm : FulltextSearch::processSearchTerm($this->searchTerm)
            );
    }
}
