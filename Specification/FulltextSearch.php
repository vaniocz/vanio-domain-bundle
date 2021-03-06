<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;
use Vanio\DomainBundle\Doctrine\QueryBuilderUtility;

class FulltextSearch implements Filter
{
    /** @var string */
    private $searchTerm;

    /** @var string */
    private $searchDocumentField;

    /** @var string|null */
    private $dqlAlias;

    /** @var bool */
    private $isPlaintext = false;

    public function __construct(
        string $searchTerm,
        string $searchDocumentField = 'fulltextDocument',
        string $dqlAlias = null
    ) {
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
     * @return string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function getFilter(QueryBuilder $queryBuilder, $dqlAlias): string
    {
        $parameter = QueryBuilderUtility::generateUniqueDqlAlias(__CLASS__);
        $queryBuilder->setParameter(
            $parameter,
            $this->isPlaintext ? $this->searchTerm : self::processSearchTerm($this->searchTerm)
        );

        return sprintf(
            '%s(%s.%s, :%s) = true',
            $this->isPlaintext ? 'plain_tsquery' : 'tsquery',
            $this->dqlAlias ?? $dqlAlias,
            $this->searchDocumentField,
            $parameter
        );
    }

    /**
     * Add/transform search operators and escape special characters.
     */
    public static function processSearchTerm(string $searchTerm): string
    {
        static $firstGroup = [
            '~[()&\\\:]+|^!+$|!+$|[&|*:]+!+|!+[&|*:]+$|(?:^|\s+)\*+~' => '',
            '~!\*|!+\s*~' => '!',
            '~"+\*+|"+~' => '"',
        ];
        $secondGroup = [
            '~([^!|&\s]*)"(.+)"([^!|&\s]*)~' => function (array $match): string {
                return trim(
                    $match[1] . ' (' . trim(preg_replace('~\s+~', '&', trim($match[2])), '&') . ') ' . $match[3]
                );
            },
        ];
        static $thirdGroup = [
            '~"|\(\s*\)~' => '',
            '~\s*\<([\-0-9]{1,1})\>\s*~' => '<\1>',
            '~([^*]+)\*+~' => '\1:*',
            '~[\s|]+~' => '|',
        ];

        $result = preg_replace(array_keys($firstGroup), $firstGroup, trim($searchTerm));
        $result = preg_replace_callback_array($secondGroup, $result);
        $result = trim(preg_replace(array_keys($thirdGroup), $thirdGroup, $result), '&|');

        return $result;
    }
}
