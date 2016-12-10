<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;

class TextSearchQuery extends TextSearchSpecification implements Filter
{
    /** @var bool */
    private static $searchConfigurationSetUp = false;

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
     */
    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias): string
    {
        if (!self::$searchConfigurationSetUp) {
            $this->setUpSearchConfiguration($queryBuilder);
        }

        $queryBuilder->setParameter('_searchQuery', $this->processSearchTerm($this->searchTerm));

        return sprintf(
            'TSQUERY(%s.%s, :_searchQuery) = true',
            $this->dqlAlias ?? $dqlAlias,
            $this->searchDocumentField
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    private function setUpSearchConfiguration(QueryBuilder $queryBuilder)
    {
        /*$db = $queryBuilder->getEntityManager()->getConnection();
        if (!$db->fetchColumn("SELECT true FROM pg_ts_dict WHERE dictname = 'cs'")) {
            $db->query('CREATE TEXT SEARCH DICTIONARY cs (template=ispell, dictfile = czech, afffile=czech, stopwords=czech)');
            $db->query('CREATE TEXT SEARCH CONFIGURATION ru (copy=english)');
            $db->query('ALTER TEXT SEARCH CONFIGURATION ru ALTER MAPPING FOR word, asciiword WITH cs, simple');
        }*/

        self::$searchConfigurationSetUp = true;
    }
}
