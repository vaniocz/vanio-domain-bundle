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
     * @param string $table
     * @param string[] $columns
     * @param array $options
     */
    public function updateTableForeignKeyOptions(string $table, array $columns, array $options)
    {
        $foreignKey = $this->getTableForeignKey($table, $columns);
        $foreignKey = new ForeignKeyConstraint(
            $foreignKey->getLocalColumns(),
            $foreignKey->getForeignTableName(),
            $foreignKey->getForeignColumns(),
            $foreignKey->getName(),
            $options + $foreignKey->getOptions()
        );
        $this->schemaManager->dropAndCreateForeignKey($foreignKey, $table);
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
