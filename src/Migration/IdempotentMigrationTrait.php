<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;

/**
 * Helpers pour migrations sûres sur base vide ou déjà partiellement peuplée.
 *
 * @phpstan-require-extends AbstractMigration
 */
trait IdempotentMigrationTrait
{
    abstract protected function addSql(string $sql, array $params = [], array $types = []): void;

    /** @var Connection */
    protected $connection;

    protected function tableExists(string $table): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$table]);
    }

    protected function columnExists(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        $columns = $this->connection->createSchemaManager()->listTableColumns($table);
        foreach ($columns as $name => $col) {
            if (strcasecmp((string) $name, $column) === 0 || strcasecmp($col->getName(), $column) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        foreach ($this->connection->createSchemaManager()->listTableIndexes($table) as $name => $index) {
            if (strcasecmp((string) $name, $indexName) === 0 || strcasecmp($index->getName(), $indexName) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function foreignKeyExists(string $table, string $fkName): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        foreach ($this->connection->createSchemaManager()->listTableForeignKeys($table) as $fk) {
            if (strcasecmp($fk->getName(), $fkName) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function addSqlIf(bool $condition, string $sql): void
    {
        if ($condition) {
            $this->addSql($sql);
        }
    }
}
