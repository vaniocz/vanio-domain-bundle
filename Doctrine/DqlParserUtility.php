<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Vanio\Stdlib\Objects;

abstract class DqlParserUtility
{
    public static function isSubselectNextToken(Parser $parser): bool
    {
        $lexer = $parser->getLexer();

        return $lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS) && $lexer->glimpse()['type'] === Lexer::T_SELECT
            || $lexer->isNextToken(Lexer::T_SELECT);
    }

    public static function parseSubselect(Parser $parser): SelectStatement
    {
        $lexer = $parser->getLexer();

        if ($isParenthesized = $lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $parser->match(Lexer::T_OPEN_PARENTHESIS);
        }

        $selectStatement = new SelectStatement(
            $parser->SelectClause(),
            $lexer->isNextToken(Lexer::T_FROM) ? $parser->FromClause() : null
        );
        $selectStatement->whereClause = $lexer->isNextToken(Lexer::T_WHERE) ? $parser->WhereClause() : null;

        if ($lexer->isNextToken(Lexer::T_GROUP)) {
            $selectStatement->groupByClause = self::parseGroupBy($parser);
        }

        $selectStatement->havingClause = $lexer->isNextToken(Lexer::T_HAVING) ? $parser->HavingClause() : null;
        $selectStatement->orderByClause = $lexer->isNextToken(Lexer::T_ORDER) ? $parser->OrderByClause() : null;

        if ($isParenthesized) {
            $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        }

        return $selectStatement;
    }

    public static function parseGroupBy(Parser $parser): GroupByClause
    {
        $parser->match(Lexer::T_GROUP);
        $parser->match(Lexer::T_BY);
        $groupByItems = [self::parseGroupByItem($parser)];

        while ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $groupByItems[] = self::parseGroupByItem($parser);
        }

        return new GroupByClause($groupByItems);
    }

    /**
     * @param Parser $parser
     * @return PathExpression|SimpleArithmeticExpression|string
     */
    public static function parseGroupByItem(Parser $parser)
    {
        try {
            return $parser->GroupByItem();
        } catch (QueryException $e) {}

        return $parser->SimpleArithmeticExpression();
    }
    
    public static function walkSubselect(SqlWalker $sqlWalker, SelectStatement $subselect): string
    {
        $rootAliases = &Objects::getPropertyValue($sqlWalker, 'rootAliases', SqlWalker::class);
        $originalRootAliases = $rootAliases;
        $rootAliases = [];

        $sql = $sqlWalker->walkSelectClause($subselect->selectClause);

        if ($subselect->fromClause) {
            $sql .= $sqlWalker->walkFromClause($subselect->fromClause);
        }

        $sql .= $sqlWalker->walkWhereClause($subselect->whereClause);

        if ($subselect->groupByClause) {
            $sql .= self::walkGroupBy($sqlWalker, $subselect->groupByClause);
        }

        if ($subselect->havingClause) {
            $sql .= $sqlWalker->walkHavingClause($subselect->havingClause);
        }

        if ($subselect->orderByClause) {
            $sql .= $sqlWalker->walkOrderByClause($subselect->orderByClause);
        }

        $rootAliases = $originalRootAliases;

        return "($sql)";
    }

    public static function walkGroupBy(SqlWalker $sqlWalker, GroupByClause $groupBy): string
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
