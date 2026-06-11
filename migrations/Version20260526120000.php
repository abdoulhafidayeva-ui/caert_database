<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Production modernization: audit_log table and performance indexes on all_data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_log (
            id INT AUTO_INCREMENT NOT NULL,
            action VARCHAR(64) NOT NULL,
            entity_type VARCHAR(128) NOT NULL,
            entity_id INT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            payload JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            actor_id INT DEFAULT NULL,
            INDEX idx_audit_entity (entity_type, entity_id),
            INDEX idx_audit_created (created_at),
            INDEX idx_audit_action (action),
            INDEX IDX_F6E1C0F510DAF24A (actor_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F510DAF24A FOREIGN KEY (actor_id) REFERENCES `users` (id) ON DELETE SET NULL');

        $this->addSql('CREATE INDEX idx_all_data_published_date ON all_data (is_published, date_attaque)');
        $this->addSql('CREATE INDEX idx_all_data_country_created ON all_data (pays_id, created_at)');
        $this->addSql('CREATE INDEX idx_all_data_user ON all_data (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F510DAF24A');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP INDEX idx_all_data_published_date ON all_data');
        $this->addSql('DROP INDEX idx_all_data_country_created ON all_data');
        $this->addSql('DROP INDEX idx_all_data_user ON all_data');
    }
}
