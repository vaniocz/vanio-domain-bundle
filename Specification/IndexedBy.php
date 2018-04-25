<?php
namespace Vanio\DomainBundle\Specification;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;

class IndexedBy implements QueryModifier
{
    /** @var string */
    private $indexBy;

    /** @var string|null */
    private $dqlAlias;

    public function __construct(string $indexBy, string $dqlAlias = null)
    {
        $this->indexBy = $indexBy;
        $this->dqlAlias = $dqlAlias;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias = null)
    {
        if ($this->dqlAlias !== null) {
            $dqlAlias = $this->dqlAlias;
        }

        /** @var From[] $froms */
        $froms = $queryBuilder->getDQLPart('from');

        foreach ($froms as &$from) {
            if ($from->getAlias() === $dqlAlias) {
                $from = new From($from->getFrom(), $dqlAlias, sprintf('%s.%s', $dqlAlias, $this->indexBy));
                break;
            }
        }

        $queryBuilder->add('from', $froms);
    }
}
