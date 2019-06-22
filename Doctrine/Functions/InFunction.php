<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\Common\Lexer\AbstractLexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Vanio\Stdlib\Objects;

class InFunction extends FunctionNode
{
    /** @var Node|null */
    private $field;

    /** @var Subselect|null */
    private $subselect;

    /** @var int|null */
    private $limit;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->field = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->subselect = $parser->Subselect();
        $this->limit = null;
        $lexer = $parser->getLexer();

        if ((strtoupper($lexer->lookahead['value'] ?? '')) === 'LIMIT') {
            $lexer->moveNext();
            $parser->match(Lexer::T_INTEGER);
            $this->limit = $lexer->token['value'];
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        $position = Objects::getPropertyValue($lexer, 'position', AbstractLexer::class);
        $tokens = &Objects::getPropertyValue($lexer, 'tokens', AbstractLexer::class);

        if (isset($tokens[$position])) {
            $position--;
        } else {
            $lexer->resetPosition($position + 1);
        }

        array_splice($tokens, $position, 0, [
            [
                'value' => '=',
                'type' => Lexer::T_EQUALS,
                'position' => -1,
            ], [
                'value' => '1',
                'type' => Lexer::T_INTEGER,
                'position' => -1,
            ],
        ]);
        $lexer->lookahead = $tokens[$position];
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            "%s IN (%s%s) AND 1",
            $this->field->dispatch($sqlWalker),
            $this->subselect->dispatch($sqlWalker),
            $this->limit ? " LIMIT {$this->limit}" : ''
        );
    }
}
