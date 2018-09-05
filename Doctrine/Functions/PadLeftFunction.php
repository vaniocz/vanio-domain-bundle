<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Pads string without truncating.
 */
class PadLeftFunction extends FunctionNode
{
    /** @var Node */
    private $string;

    /** @var Node */
    private $length;

    /** @var Node|null */
    private $padding;

    /** @var Node */
    private $type;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->length = $parser->ArithmeticPrimary();

        if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->padding = $parser->ArithmeticPrimary();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $string = $this->string->dispatch($sqlWalker);
        $length = $this->length->dispatch($sqlWalker);
        $padding = $this->padding ? $this->padding->dispatch($sqlWalker) : $sqlWalker->walkStringPrimary(' ');

        return sprintf(
            'CONCAT(REPEAT(%s, (%s - LENGTH(%s)) / LENGTH(%s)), %s, %s)',
            $padding,
            $length,
            $string,
            $padding,
            $sqlWalker->getConnection()->getDatabasePlatform()->getSubstringExpression(
                $sqlWalker->walkArithmeticPrimary($this->padding),
                1,
                sprintf(
                    '(%d - LENGTH(%s)) %% LENGTH(%s)',
                    $length,
                    $string,
                    $padding
                )
            ),
            $string
        );
    }
}
