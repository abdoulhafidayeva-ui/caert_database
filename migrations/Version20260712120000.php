<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\IdempotentMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712120000 extends AbstractMigration
{
    use IdempotentMigrationTrait;

    public function getDescription(): string
    {
        return 'Add region_id on users for focal/staff default data scope';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('users')) {
            return;
        }

        $this->addSqlIf(
            !$this->columnExists('users', 'region_id'),
            'ALTER TABLE `users` ADD region_id INT DEFAULT NULL'
        );
        $this->addSqlIf(
            !$this->foreignKeyExists('users', 'FK_1483A5E998260155'),
            'ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E998260155 FOREIGN KEY (region_id) REFERENCES region (id)'
        );
        $this->addSqlIf(
            !$this->indexExists('users', 'IDX_1483A5E998260155'),
            'CREATE INDEX IDX_1483A5E998260155 ON `users` (region_id)'
        );

        if ($this->tableExists('pays') && $this->columnExists('users', 'region_id')) {
            $this->addSql('UPDATE `users` u INNER JOIN pays p ON u.pays_id = p.id SET u.region_id = p.region_id WHERE u.region_id IS NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('users') || !$this->columnExists('users', 'region_id')) {
            return;
        }

        $this->addSqlIf($this->foreignKeyExists('users', 'FK_1483A5E998260155'), 'ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E998260155');
        $this->addSqlIf($this->indexExists('users', 'IDX_1483A5E998260155'), 'DROP INDEX IDX_1483A5E998260155 ON `users`');
        $this->addSql('ALTER TABLE `users` DROP region_id');
    }
}
