<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\QueryBuilder;
use Vanio\Stdlib\Strings;

abstract class QueryBuilderUtility
{
    /**
     * @return string[]
     */
    public static function resolveDqlAliasClasses(QueryBuilder $queryBuilder): array
    {
        $dqlAliasClasses = [];

        /** @var From $from */
        foreach ($queryBuilder->getDQLPart('from') as $from) {
            $dqlAliasClasses[$from->getAlias()] = $from->getFrom();
        }

        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            /** @var Join $join */
            foreach ($joins as $join) {
                list($dqlAlias, $association) = explode('.', $join->getJoin(), 2) + [null, null];

                if ($association) {
                    $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata($dqlAliasClasses[$dqlAlias]);
                    $dqlAliasClasses[$join->getAlias()] = $classMetadata->getAssociationTargetClass($association);
                } else {
                    $dqlAliasClasses[$join->getAlias()] = $join->getJoin();
                }
            }
        }

        return $dqlAliasClasses;
    }

    public static function removeUnusedQueryParameters(Query $query): void
    {
        $parameters = [];

        foreach ($query->getParameters() as $parameter) {
            assert($parameter instanceof Parameter);
            $parameters[$parameter->getName()] = $parameter->getValue();
        }

        $parse = function () {
            return $this->_parse();
        };
        $parse = $parse->bindTo($query, Query::class);
        $parserResult = $parse();
        assert($parserResult instanceof ParserResult);
        $query->setParameters([]);

        foreach ($parserResult->getParameterMappings() as $name => $_) {
            if (array_key_exists($name, $parameters)) {
                $query->setParameter($name, $parameters[$name]);
            }
        }
    }

    /**
     * @param mixed $literal
     * @return string
     */
    public static function quoteLiteral($literal): string
    {
        if (is_array($literal)) {
            return implode(', ', array_map([__CLASS__, __FUNCTION__], $literal));
        } elseif (is_int($literal) || is_float($literal)) {
            return (string) $literal;
        } else if (is_bool($literal)) {
            return $literal ? 'true' : 'false';
        }

        return sprintf("'%s'", str_replace("'", "''", $literal));
    }

    public static function findJoin(
        QueryBuilder $queryBuilder,
        string $dqlAlias,
        ?string $joinType = null,
        ?string $join = null,
        ?string $condition = null
    ): ?Join {
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $dqlPart) {
                /** @var Join $dqlPart */
                if ($dqlPart->getAlias() !== $dqlAlias) {
                    continue;
                } elseif (
                    ($joinType === null || $dqlPart->getJoinType() === $joinType)
                    && ($join === null || $dqlPart->getJoin() === $join)
                    && ($condition === null || (string) $dqlPart->getCondition() === $condition)
                    && ($dqlPart->getConditionType() ?? 'WITH') === 'WITH'
                ) {
                    return $dqlPart;
                }

                throw new \InvalidArgumentException("Different DQL join alias \"{$dqlAlias}\" is already defined.");
            }
        }

        return null;
    }

    public static function generateUniqueDqlAlias(string $class): string
    {
        static $i = 0;

        return Strings::baseName($class) . $i++;
    }
}
