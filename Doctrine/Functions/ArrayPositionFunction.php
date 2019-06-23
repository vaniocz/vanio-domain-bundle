<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class ArrayPositionFunction extends FunctionNode
{
    /** @var Node */
    private $array;

    /** @var Node */
    private $element;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->array = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->element = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            "ARRAY_POSITION(%s, %s)",
            $this->array->dispatch($sqlWalker),
            $this->element->dispatch($sqlWalker)
        );
    }
}
