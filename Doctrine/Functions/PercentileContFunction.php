<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class PercentileContFunction extends FunctionNode
{
    /** @var Node */
    private $fraction;

    /** @var OrderByClause */
    private $orderByClause;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->fraction = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        $lexer = $parser->getLexer();

        if ($lexer->lookahead['value'] !== 'WITHIN') {
            $parser->syntaxError('WITHIN GROUP', $lexer->lookahead['value']);
        }

        $lexer->moveNext();

        if ($lexer->lookahead['value'] !== 'GROUP') {
            $parser->syntaxError('GROUP', $lexer->lookahead);
        }

        $lexer->moveNext();
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->orderByClause = $parser->OrderByClause();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'percentile_cont(%s) WITHIN GROUP(%s)',
            $this->fraction->dispatch($sqlWalker),
            $this->orderByClause->dispatch($sqlWalker)
        );
    }
}
