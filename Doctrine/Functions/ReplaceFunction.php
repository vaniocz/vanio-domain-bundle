<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class ReplaceFunction extends FunctionNode
{
    /** @var Node */
    private $string;

    /** @var Node */
    private $search;

    /** @var Node */
    private $replace;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->search = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->replace = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'REPLACE(%s, %s, %s)',
            $this->string->dispatch($sqlWalker),
            $this->search->dispatch($sqlWalker),
            $this->replace->dispatch($sqlWalker)
        );
    }
}
