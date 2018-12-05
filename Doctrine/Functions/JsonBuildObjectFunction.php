<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

class JsonBuildObjectFunction extends FunctionNode
{
    /** @var SelectStatement|null */
    private $select;

    /** @var mixed[] */
    private $arguments = [];

    public function parse(Parser $parser): void
    {
        $this->subselect = null;
        $this->arguments = [];
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        if ($parser->getLexer()->isNextToken(Lexer::T_SELECT)) {
            $this->select = $this->parseSelect($parser);
        } else {
            $this->arguments = $this->parseArguments($parser);
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        if ($this->select) {
            $argumentNameSqls = [];

            for ($i = 1; $i <= count($this->select->selectClause->selectExpressions); $i++) {
                $argumentNameSqls[] = "_argument_{$i}";
            }

            $argumentNamesSql = implode(', ', $argumentNameSqls);

            return sprintf(
                '(SELECT JSON_BUILD_OBJECT(%s) FROM (%s) _json_build_object (%s))',
                $argumentNamesSql,
                $this->walkSelect($sqlWalker, $this->select),
                $argumentNamesSql
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

        return sprintf('JSON_BUILD_OBJECT(%s)', implode(', ', $argumentSqls));
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
        $lexer = $parser->getLexer();

        if ($lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS) && $lexer->glimpse()['type'] === Lexer::T_SELECT) {
            $parser->match(Lexer::T_OPEN_PARENTHESIS);
            $subselect = $this->parseSubselect($parser);
            $parser->match(Lexer::T_CLOSE_PARENTHESIS);

            return $subselect;
        }

        return $parser->SimpleArithmeticExpression();
    }

    private function parseSubselect(Parser $parser): Subselect
    {
        $lexer = $parser->getLexer();
        $subselect = new Subselect(
            $parser->SimpleSelectClause(),
            $lexer->isNextToken(Lexer::T_FROM) ? $parser->SubselectFromClause() : null
        );
        $subselect->whereClause   = $lexer->isNextToken(Lexer::T_WHERE) ? $parser->WhereClause() : null;
        $subselect->groupByClause = $lexer->isNextToken(Lexer::T_GROUP) ? $parser->GroupByClause() : null;
        $subselect->havingClause  = $lexer->isNextToken(Lexer::T_HAVING) ? $parser->HavingClause() : null;
        $subselect->orderByClause = $lexer->isNextToken(Lexer::T_ORDER) ? $parser->OrderByClause() : null;

        return $subselect;
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

    private function walkArgument(SqlWalker $sqlWalker, Node $argument): string
    {
        return $argument instanceof Subselect
            ? $this->walkSubselect($sqlWalker, $argument)
            : $argument->dispatch($sqlWalker);
    }

    public function walkSubselect(SqlWalker $sqlWalker, Subselect $subselect): string
    {
        $sql = $sqlWalker->walkSimpleSelectClause($subselect->simpleSelectClause);

        if ($subselect->subselectFromClause) {
            $sql .= $sqlWalker->walkSubselectFromClause($subselect->subselectFromClause);
        }

        $sql .= $sqlWalker->walkWhereClause($subselect->whereClause);
        $sql .= $subselect->groupByClause ? $sqlWalker->walkGroupByClause($subselect->groupByClause) : '';
        $sql .= $subselect->havingClause ? $sqlWalker->walkHavingClause($subselect->havingClause) : '';
        $sql .= $subselect->orderByClause ? $sqlWalker->walkOrderByClause($subselect->orderByClause) : '';

        return "({$sql})";
    }
}
