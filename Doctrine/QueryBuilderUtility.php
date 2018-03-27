<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class QueryBuilderUtility
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
}
