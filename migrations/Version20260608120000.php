<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add users.locale for UI language preference';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `users` ADD locale VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `users` DROP locale');
    }
}
