<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class RegexpReplaceFunction extends FunctionNode
{
    /** @var Node */
    private $string;

    /** @var Node */
    private $pattern;

    /** @var Node */
    private $replacement;

    /** @var Node */
    private $flags;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->pattern = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->replacement = $parser->ArithmeticPrimary();
        $this->flags = null;

        if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->flags = $parser->ArithmeticPrimary();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'REGEXP_REPLACE(%s, %s, %s%s)',
            $this->string->dispatch($sqlWalker),
            $this->pattern->dispatch($sqlWalker),
            $this->replacement->dispatch($sqlWalker),
            $this->flags ? ", {$this->flags->dispatch($sqlWalker)}" : ''
        );
    }
}
