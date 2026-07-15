<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\IdempotentMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607120000 extends AbstractMigration
{
    use IdempotentMigrationTrait;

    public function getDescription(): string
    {
        return 'Allow multiple users to be linked to the same pays (drop unique index on users.pays_id)';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('users')) {
            return;
        }

        // Ancien schéma : UNIQUE sur pays_id — à retirer si présent
        if ($this->indexExists('users', 'UNIQ_1483A5E9A6E44244')) {
            $this->addSqlIf(
                $this->foreignKeyExists('users', 'FK_1483A5E9A6E44244'),
                'ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E9A6E44244'
            );
            $this->addSql('DROP INDEX UNIQ_1483A5E9A6E44244 ON `users`');
            $this->addSqlIf(
                !$this->indexExists('users', 'IDX_1483A5E9A6E44244'),
                'CREATE INDEX IDX_1483A5E9A6E44244 ON `users` (pays_id)'
            );
            $this->addSqlIf(
                !$this->foreignKeyExists('users', 'FK_1483A5E9A6E44244'),
                'ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E9A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('users') || $this->indexExists('users', 'UNIQ_1483A5E9A6E44244')) {
            return;
        }

        $this->addSqlIf($this->foreignKeyExists('users', 'FK_1483A5E9A6E44244'), 'ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E9A6E44244');
        $this->addSqlIf($this->indexExists('users', 'IDX_1483A5E9A6E44244'), 'DROP INDEX IDX_1483A5E9A6E44244 ON `users`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9A6E44244 ON `users` (pays_id)');
        $this->addSql('ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E9A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)');
    }
}
