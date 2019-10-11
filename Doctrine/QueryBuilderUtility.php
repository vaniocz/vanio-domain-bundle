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

    public static function setQueryParameters(Query $query, iterable $parameters): void
    {
        $params = [];
        $query->setParameters([]);

        foreach ($parameters as $name => $value) {
            if ($value instanceof Parameter) {
                $name = $value->getName();
                $value = $value->getValue();
            }

            $params[$name] = $value;
        }

        $parse = function () {
            return $this->_parse();
        };
        $parse = $parse->bindTo($query, Query::class);
        $parserResult = $parse();
        assert($parserResult instanceof ParserResult);

        foreach ($parserResult->getParameterMappings() as $name => $_) {
            $query->setParameter($name, $params[$name]);
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

    public static function generateUniqueDqlAlias(string $class): string
    {
        static $i = 0;

        return Strings::baseName($class) . $i++;
    }
}
