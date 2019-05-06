<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Vanio\DomainBundle\Doctrine\DqlParserUtility;

class JsonBuildObjectFunction extends FunctionNode
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
            $argumentNameSqls = [];

            for ($i = 1; $i <= count($this->subselect->selectClause->selectExpressions); $i++) {
                $argumentNameSqls[] = "_argument_{$i}";
            }

            $argumentNamesSql = implode(', ', $argumentNameSqls);

            return sprintf(
                "(SELECT {$this->name}({$argumentNamesSql}) FROM (%s) _{$this->name} ({$argumentNamesSql}))",
                DqlParserUtility::walkSubselect($sqlWalker, $this->subselect)
            );
        }

        $argumentSqls = [];

        foreach ($this->arguments as list($isDistinct, $argument)) {
            $argumentSqls[] = sprintf(
                '%s %s',
                $isDistinct ? 'DISTINCT' : '',
                $this->walkArgument($sqlWalker, $argument)
            );
        }

        return sprintf("{$this->name}(%s)", implode(', ', $argumentSqls));
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

            $arguments[] = [
                $isDistinct,
                $isDistinct ? $parser->SimpleArithmeticExpression() : $this->parseArgument($parser)
            ];

            if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
                $parser->match(Lexer::T_COMMA);
            } else {
                break;
            }
        }

        return $arguments;
    }

    /**
     * @return Subselect|SimpleArithmeticExpression
     */
    private function parseArgument(Parser $parser): Node
    {
        return DqlParserUtility::isSubselectNextToken($parser)
            ? DqlParserUtility::parseSubselect($parser)
            : $parser->SimpleArithmeticExpression();
    }

    private function walkArgument(SqlWalker $sqlWalker, Node $argument): string
    {
        return $argument instanceof SelectStatement
            ? DqlParserUtility::walkSubselect($sqlWalker, $argument)
            : $argument->dispatch($sqlWalker);
    }
}
