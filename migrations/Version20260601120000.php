<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\IdempotentMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601120000 extends AbstractMigration
{
    use IdempotentMigrationTrait;

    public function getDescription(): string
    {
        return 'Production indexes: date_attaque, workflow inbox, FK analytics columns';
    }

    public function up(Schema $schema): void
    {
        $indexes = [
            ['all_data', 'idx_all_data_date_attaque', 'CREATE INDEX idx_all_data_date_attaque ON all_data (date_attaque)'],
            ['all_data', 'idx_all_data_workflow', 'CREATE INDEX idx_all_data_workflow ON all_data (is_published, objet_rejet(64))'],
            ['all_data', 'idx_all_data_attaque', 'CREATE INDEX idx_all_data_attaque ON all_data (attaque_id)'],
            ['all_data', 'idx_all_data_cible', 'CREATE INDEX idx_all_data_cible ON all_data (cible_id)'],
            ['all_data', 'idx_all_data_perpetrateur', 'CREATE INDEX idx_all_data_perpetrateur ON all_data (perpetrateur_id)'],
            ['users', 'idx_users_enable', 'CREATE INDEX idx_users_enable ON users (enable)'],
        ];

        foreach ($indexes as [$table, $name, $sql]) {
            $this->addSqlIf($this->tableExists($table) && !$this->indexExists($table, $name), $sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSqlIf($this->indexExists('all_data', 'idx_all_data_date_attaque'), 'DROP INDEX idx_all_data_date_attaque ON all_data');
        $this->addSqlIf($this->indexExists('all_data', 'idx_all_data_workflow'), 'DROP INDEX idx_all_data_workflow ON all_data');
        $this->addSqlIf($this->indexExists('all_data', 'idx_all_data_attaque'), 'DROP INDEX idx_all_data_attaque ON all_data');
        $this->addSqlIf($this->indexExists('all_data', 'idx_all_data_cible'), 'DROP INDEX idx_all_data_cible ON all_data');
        $this->addSqlIf($this->indexExists('all_data', 'idx_all_data_perpetrateur'), 'DROP INDEX idx_all_data_perpetrateur ON all_data');
        $this->addSqlIf($this->indexExists('users', 'idx_users_enable'), 'DROP INDEX idx_users_enable ON users');
    }
}
