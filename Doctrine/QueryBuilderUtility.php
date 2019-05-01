<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
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
