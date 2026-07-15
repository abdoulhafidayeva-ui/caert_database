<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\IdempotentMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608120000 extends AbstractMigration
{
    use IdempotentMigrationTrait;

    public function getDescription(): string
    {
        return 'Add users.locale for UI language preference';
    }

    public function up(Schema $schema): void
    {
        $this->addSqlIf(
            $this->tableExists('users') && !$this->columnExists('users', 'locale'),
            'ALTER TABLE `users` ADD locale VARCHAR(5) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSqlIf(
            $this->tableExists('users') && $this->columnExists('users', 'locale'),
            'ALTER TABLE `users` DROP locale'
        );
    }
}
