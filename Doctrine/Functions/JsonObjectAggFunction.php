<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

class JsonObjectAggFunction extends FunctionNode
{
    /** @var SelectStatement|null */
    private $select;

    /** @var mixed[] */
    private $arguments = [];

    public function parse(Parser $parser): void
    {
        $this->select = null;
        $this->arguments = [];
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $lexer = $parser->getLexer();
        $isParenthesized = $lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS);

        if ($isParenthesized && $lexer->glimpse()['type'] === Lexer::T_SELECT || $lexer->isNextToken(Lexer::T_SELECT)) {
            if ($isParenthesized) {
                $parser->match(Lexer::T_OPEN_PARENTHESIS);
            }

            $this->select = $this->parseSelect($parser);

            if ($isParenthesized) {
                $parser->match(Lexer::T_CLOSE_PARENTHESIS);
            }
        } else {
            $this->arguments = $this->parseArguments($parser);
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        if ($this->select) {
            return sprintf(
                '(SELECT JSON_OBJECT_AGG(_key, _value) FROM (%s) _json_object_agg (_key, _value))',
                $this->walkSelect($sqlWalker, $this->select)
            );
        }

        $argumentSqls = [];

        foreach ($this->arguments as list($isDistinct, $argument)) {
            assert($argument instanceof Node);
            $argumentSqls[] = sprintf('%s %s', $isDistinct ? 'DISTINCT' : '', $argument->dispatch($sqlWalker));
        }

        return sprintf('JSON_OBJECT_AGG(%s)', implode(', ', $argumentSqls));
    }

    private function parseSelect(Parser $parser): SelectStatement
    {
        $selectStatement = new SelectStatement($parser->SelectClause(), $parser->FromClause());
        $lexer = $parser->getLexer();
        $selectStatement->whereClause = $lexer->isNextToken(Lexer::T_WHERE) ? $parser->WhereClause() : null;

        if ($lexer->isNextToken(Lexer::T_GROUP)) {
            $selectStatement->groupByClause = $this->parseGroupBy($parser);
        }

        $selectStatement->havingClause = $lexer->isNextToken(Lexer::T_HAVING) ? $parser->HavingClause() : null;
        $selectStatement->orderByClause = $lexer->isNextToken(Lexer::T_ORDER) ? $parser->OrderByClause() : null;

        return $selectStatement;
    }

    private function parseGroupBy(Parser $parser): GroupByClause
    {
        $parser->match(Lexer::T_GROUP);
        $parser->match(Lexer::T_BY);
        $groupByItems = [$this->parseGroupByItem($parser)];

        while ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $groupByItems[] = $this->parseGroupByItem($parser);
        }

        return new GroupByClause($groupByItems);
    }

    /**
     * @param Parser $parser
     * @return PathExpression|SimpleArithmeticExpression|string
     */
    private function parseGroupByItem(Parser $parser)
    {
        try {
            return $parser->GroupByItem();
        } catch (QueryException $e) {}

        return $parser->SimpleArithmeticExpression();
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

    private function walkSelect(SqlWalker $sqlWalker, SelectStatement $select): string
    {
        $sql = $sqlWalker->walkSelectClause($select->selectClause)
            . $sqlWalker->walkFromClause($select->fromClause)
            . $sqlWalker->walkWhereClause($select->whereClause);

        if ($select->groupByClause) {
            $sql .= $this->walkGroupBy($sqlWalker, $select->groupByClause);
        }

        if ($select->havingClause) {
            $sql .= $sqlWalker->walkHavingClause($select->havingClause);
        }

        if ($select->orderByClause) {
            $sql .= $sqlWalker->walkOrderByClause($select->orderByClause);
        }

        return $sql;
    }

    private function walkGroupBy(SqlWalker $sqlWalker, GroupByClause $groupBy): string
    {
        $groupByItemSqls = [];

        foreach ($groupBy->groupByItems as $groupByItem) {
            $groupByItemSqls[] = $groupByItem instanceof Node
                ? $groupByItem->dispatch($sqlWalker)
                : $sqlWalker->walkGroupByItem($groupByItem);
        }

        return sprintf(' GROUP BY %s', implode(', ', $groupByItemSqls));
    }
}
