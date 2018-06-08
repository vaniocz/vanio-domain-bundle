<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;

class ForeignKeysHelper
{
    /** @var AbstractSchemaManager */
    private $schemaManager;

    public function __construct(AbstractSchemaManager $schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    /**
     * @param string $localTableName
     * @param string[]|string $localColumnNames
     * @param string $foreignTableName
     * @param string[]|string $foreignColumnNames
     * @param array $options
     * @param string|null $foreignKeyName
     */
    public function addTableForeignKey(
        string $localTableName,
        $localColumnNames,
        string $foreignTableName,
        $foreignColumnNames,
        array $options,
        string $foreignKeyName = null
    ): void {
        $localColumnNames = (array) $localColumnNames;
        $foreignColumnNames = (array) $foreignColumnNames;
        $foreignKeyName = $foreignKeyName ?? sprintf('fk_%s_%s', $localTableName, implode('_', $localColumnNames));

        $foreignKey = new ForeignKeyConstraint($localColumnNames, $foreignTableName, $foreignColumnNames, $foreignKeyName, $options);
        $this->schemaManager->createForeignKey($foreignKey, $this->schemaManager->getDatabasePlatform()->quoteSingleIdentifier($localTableName));
    }

    /**
     * @param string $table
     * @param string[]|string $columns
     * @param array $options
     */
    public function updateTableForeignKeyOptions(string $table, $columns, array $options)
    {
        $columns = (array) $columns;

        $foreignKey = $this->getTableForeignKey($table, $columns);
        $foreignKey = new ForeignKeyConstraint(
            $foreignKey->getLocalColumns(),
            $foreignKey->getForeignTableName(),
            $foreignKey->getForeignColumns(),
            $foreignKey->getName(),
            $options + $foreignKey->getOptions()
        );
        $this->schemaManager->dropAndCreateForeignKey($foreignKey, $this->schemaManager->getDatabasePlatform()->quoteSingleIdentifier($table));
    }

    /**
     * @param string $table
     * @param string[] $columns
     * @return ForeignKeyConstraint
     * @throws \RuntimeException
     */
    public function getTableForeignKey(string $table, array $columns): ForeignKeyConstraint
    {
        $foreignKeys = $this->schemaManager->listTableForeignKeys($table);

        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->intersectsIndexColumns(new Index('', $columns))) {
                return $foreignKey;
            }
        }

        throw new \RuntimeException(sprintf(
            'Unable to find foreign key on column(s) "%s" in table "%s".',
            implode('", "', $columns),
            $table
        ));
    }
}
