<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class TopFunction extends FunctionNode
{
    /** @var PathExpression */
    private $limit;

    /** @var Subselect */
    private $subselect;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $parser->match(Lexer::T_INTEGER);
        $this->limit = $parser->getLexer()->token['value'];
        $parser->match(Lexer::T_COMMA);
        $this->subselect = $parser->Subselect();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf('(%s LIMIT %d)', $this->subselect->dispatch($sqlWalker), $this->limit);
    }
}
