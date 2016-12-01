<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\SqlWalker;

class TsQueryFunction extends \VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsQueryFunction
{
    public function getSql(SqlWalker $sqlWalker): string
    {
        $this->findFTSField($sqlWalker);

        return $this->ftsField->dispatch($sqlWalker)
            . " @@ to_tsquery('ru', " . $this->queryString->dispatch($sqlWalker) . ')';
    }
}
