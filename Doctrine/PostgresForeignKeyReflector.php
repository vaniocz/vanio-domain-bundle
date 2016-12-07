<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;

class PostgresForeignKeyReflector
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function reflectForeignKey(string $table, string $column): ForeignKeyConstraint
    {
        $foreignKey = $this->connection->fetchAssoc(
            "
                SELECT
                    tc.constraint_name, tc.table_name,
                    ccu.table_name AS foreign_table_name,
                    array_to_json(array_agg(DISTINCT kcu.column_name::text)) AS column_names,
                    array_to_json(array_agg(DISTINCT ccu.column_name::text)) AS foreign_column_names
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
                JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
                WHERE tc.constraint_name=(
                    SELECT tc.constraint_name
                    FROM information_schema.table_constraints AS tc
                    JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
                    WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name=:table AND kcu.column_name=:column
                )
                GROUP BY tc.table_name, tc.constraint_name, ccu.table_name
            ",
            ['table' => $table, 'column' => $column]
        );

        return new ForeignKeyConstraint(
            json_decode($foreignKey['column_names']),
            $foreignKey['foreign_table_name'],
            json_decode($foreignKey['foreign_column_names']),
            $foreignKey['constraint_name']
        );
    }
}
