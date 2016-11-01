<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class JsonbExistsAnyFunction extends FunctionNode
{
    /** @var Node */
    private $json;

    /** @var Node */
    private $array;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->json = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->array = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'jsonb_exists_any(%s, %s)',
            $this->json->dispatch($sqlWalker),
            $this->array->dispatch($sqlWalker)
        );
    }
}
