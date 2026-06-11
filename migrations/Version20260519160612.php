<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519160612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `users` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, profil VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, fonction VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, update_at DATETIME DEFAULT NULL, roles JSON DEFAULT NULL, enable TINYINT DEFAULT NULL, prenoms VARCHAR(255) NOT NULL, notify_by INT DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, is_verified TINYINT DEFAULT NULL, token_created_at DATETIME DEFAULT NULL, organisation VARCHAR(255) DEFAULT NULL, pays_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E95F37A13B (token), UNIQUE INDEX UNIQ_1483A5E9A6E44244 (pays_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E9A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299118FE712 FOREIGN KEY (attaque_id) REFERENCES attaque (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E2997104F1E6 FOREIGN KEY (materiel_attaque_id) REFERENCES materiel_attaque (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299A96E5E09 FOREIGN KEY (cible_id) REFERENCES cible (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E29981072610 FOREIGN KEY (materieaux_id) REFERENCES materiaux (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E29945C33E2D FOREIGN KEY (moyen_attaque_id) REFERENCES moyen_attaque (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299123C5581 FOREIGN KEY (perpetrateur_id) REFERENCES perpetrateurs (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299B6885C6C FOREIGN KEY (espace_id) REFERENCES espace (id)');
        $this->addSql('ALTER TABLE attaque ADD CONSTRAINT FK_95751B92A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE cible ADD CONSTRAINT FK_E15DEC3BA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE espace ADD CONSTRAINT FK_6AB096DA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE materiaux ADD CONSTRAINT FK_97C56625A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE materiel_attaque ADD CONSTRAINT FK_2D046047A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE moyen_attaque ADD CONSTRAINT FK_BE72D329A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE pays ADD CONSTRAINT FK_349F3CAE98260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE perpetrateurs ADD CONSTRAINT FK_5552D9BFA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E9A6E44244');
        $this->addSql('DROP TABLE `users`');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E299118FE712');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E2997104F1E6');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E299A96E5E09');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E29981072610');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E29945C33E2D');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E299123C5581');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E299A6E44244');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E299A76ED395');
        $this->addSql('ALTER TABLE all_data DROP FOREIGN KEY FK_7CB7E299B6885C6C');
        $this->addSql('ALTER TABLE attaque DROP FOREIGN KEY FK_95751B92A76ED395');
        $this->addSql('ALTER TABLE cible DROP FOREIGN KEY FK_E15DEC3BA76ED395');
        $this->addSql('ALTER TABLE espace DROP FOREIGN KEY FK_6AB096DA76ED395');
        $this->addSql('ALTER TABLE materiaux DROP FOREIGN KEY FK_97C56625A76ED395');
        $this->addSql('ALTER TABLE materiel_attaque DROP FOREIGN KEY FK_2D046047A76ED395');
        $this->addSql('ALTER TABLE moyen_attaque DROP FOREIGN KEY FK_BE72D329A76ED395');
        $this->addSql('ALTER TABLE pays DROP FOREIGN KEY FK_349F3CAE98260155');
        $this->addSql('ALTER TABLE perpetrateurs DROP FOREIGN KEY FK_5552D9BFA76ED395');
    }
}
