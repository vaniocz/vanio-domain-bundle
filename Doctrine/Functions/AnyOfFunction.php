<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class AnyOfFunction extends FunctionNode
{
    /** @var Subselect */
    private $subselect;

    /** @var int|null */
    private $limit;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->subselect = $parser->Subselect();
        $this->limit = null;
        $lexer = $parser->getLexer();

        if ((strtoupper($lexer->lookahead['value'] ?? '')) === 'LIMIT') {
            $lexer->moveNext();
            $parser->match(Lexer::T_INTEGER);
            $this->limit = $lexer->token['value'];
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            "ANY (%s%s)",
            $this->subselect->dispatch($sqlWalker),
            $this->limit ? " LIMIT {$this->limit}" : ''
        );
    }
}
