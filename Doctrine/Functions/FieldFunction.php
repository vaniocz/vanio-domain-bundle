<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class FieldFunction extends FunctionNode
{
    /** @var PathExpression */
    private $field;

    /** @var Node[] */
    private $elements = [];

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->field = $parser->SingleValuedPathExpression();
        $this->elements = [];

        do {
            $parser->match(Lexer::T_COMMA);
            $this->elements[] = $parser->ArithmeticPrimary();
        } while ($parser->getLexer()->isNextToken(Lexer::T_COMMA));

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $sql = '';
        $field = $this->field->dispatch($sqlWalker);

        foreach ($this->elements as $i => $element) {
            $sql = sprintf('%s WHEN %s = %s THEN %d', $sql, $field, $element->dispatch($sqlWalker), $i);
        }

        return sprintf('CASE %s ELSE %d END', $sql, $i + 1);
    }
}
