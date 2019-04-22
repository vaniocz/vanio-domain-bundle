<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Vanio\DomainBundle\Doctrine\DqlParserUtility;

class JsonObjectAggFunction extends FunctionNode
{
    /** @var SelectStatement|null */
    private $subselect;

    /** @var mixed[] */
    private $arguments = [];

    public function parse(Parser $parser): void
    {
        $this->subselect = null;
        $this->arguments = [];
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        if (DqlParserUtility::isSubselectNextToken($parser)) {
            $this->subselect = DqlParserUtility::parseSubselect($parser);
        } else {
            $this->arguments = $this->parseArguments($parser);
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        if ($this->subselect) {
            return sprintf(
                '(SELECT JSON_OBJECT_AGG(_key, _value) FROM (%s) _json_object_agg (_key, _value))',
                DqlParserUtility::walkSubselect($sqlWalker, $this->subselect)
            );
        }

        $argumentSqls = [];

        foreach ($this->arguments as list($isDistinct, $argument)) {
            assert($argument instanceof Node);
            $argumentSqls[] = sprintf('%s %s', $isDistinct ? 'DISTINCT' : '', $argument->dispatch($sqlWalker));
        }

        return sprintf('JSON_OBJECT_AGG(%s)', implode(', ', $argumentSqls));
    }

    /**
     * @param Parser $parser
     * @return mixed[]
     */
    private function parseArguments(Parser $parser): array
    {
        $arguments = [];

        while (true) {
            if ($isDistinct = $parser->getLexer()->isNextToken(Lexer::T_DISTINCT)) {
                $parser->match(Lexer::T_DISTINCT);
            }

            $arguments[] = [$isDistinct, $parser->SimpleArithmeticExpression()];

            if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
                $parser->match(Lexer::T_COMMA);
            } else {
                break;
            }
        }

        return $arguments;
    }
}
