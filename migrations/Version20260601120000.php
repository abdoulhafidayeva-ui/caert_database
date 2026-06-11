<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Final production phase — query performance indexes for analytics, workflow, and GIS.
 */
final class Version20260601120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Production indexes: date_attaque, workflow inbox, FK analytics columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_all_data_date_attaque ON all_data (date_attaque)');
        $this->addSql('CREATE INDEX idx_all_data_workflow ON all_data (is_published, objet_rejet(64))');
        $this->addSql('CREATE INDEX idx_all_data_attaque ON all_data (attaque_id)');
        $this->addSql('CREATE INDEX idx_all_data_cible ON all_data (cible_id)');
        $this->addSql('CREATE INDEX idx_all_data_perpetrateur ON all_data (perpetrateur_id)');
        $this->addSql('CREATE INDEX idx_users_enable ON users (enable)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_all_data_date_attaque ON all_data');
        $this->addSql('DROP INDEX idx_all_data_workflow ON all_data');
        $this->addSql('DROP INDEX idx_all_data_attaque ON all_data');
        $this->addSql('DROP INDEX idx_all_data_cible ON all_data');
        $this->addSql('DROP INDEX idx_all_data_perpetrateur ON all_data');
        $this->addSql('DROP INDEX idx_users_enable ON users');
    }
}
