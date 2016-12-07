<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\SqlWalker;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankFunction as BaseTsRankFunction;

class TsRankFunction extends BaseTsRankFunction
{
    public function getSql(SqlWalker $sqlWalker): string
    {
        $this->findFTSField($sqlWalker);

        return sprintf(
            "ts_rank(%s, to_tsquery('ru', %s))",
            $this->ftsField->dispatch($sqlWalker),
            $this->queryString->dispatch($sqlWalker)
        );
    }
}
