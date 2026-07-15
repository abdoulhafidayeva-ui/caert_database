<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\IdempotentMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée la table users et les FK associées (si absentes).
 */
final class Version20260519160612 extends AbstractMigration
{
    use IdempotentMigrationTrait;

    public function getDescription(): string
    {
        return 'Create users table and related foreign keys (idempotent)';
    }

    public function up(Schema $schema): void
    {
        // État final attendu : pays_id non unique (cf. Version20260607120000)
        $this->addSqlIf(!$this->tableExists('users'), <<<'SQL'
CREATE TABLE `users` (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    profil VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    fonction VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    update_at DATETIME DEFAULT NULL,
    roles JSON DEFAULT NULL,
    enable TINYINT(1) DEFAULT NULL,
    prenoms VARCHAR(255) NOT NULL,
    notify_by INT DEFAULT NULL,
    token VARCHAR(255) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT NULL,
    token_created_at DATETIME DEFAULT NULL,
    organisation VARCHAR(255) DEFAULT NULL,
    pays_id INT DEFAULT NULL,
    locale VARCHAR(5) DEFAULT NULL,
    region_id INT DEFAULT NULL,
    UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
    UNIQUE INDEX UNIQ_1483A5E95F37A13B (token),
    INDEX IDX_1483A5E9A6E44244 (pays_id),
    INDEX IDX_1483A5E998260155 (region_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        $fks = [
            ['users', 'FK_1483A5E9A6E44244', 'ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E9A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)'],
            ['users', 'FK_1483A5E998260155', 'ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E998260155 FOREIGN KEY (region_id) REFERENCES region (id)'],
            ['all_data', 'FK_7CB7E299118FE712', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299118FE712 FOREIGN KEY (attaque_id) REFERENCES attaque (id)'],
            ['all_data', 'FK_7CB7E2997104F1E6', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E2997104F1E6 FOREIGN KEY (materiel_attaque_id) REFERENCES materiel_attaque (id)'],
            ['all_data', 'FK_7CB7E299A96E5E09', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299A96E5E09 FOREIGN KEY (cible_id) REFERENCES cible (id)'],
            ['all_data', 'FK_7CB7E29981072610', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E29981072610 FOREIGN KEY (materieaux_id) REFERENCES materiaux (id)'],
            ['all_data', 'FK_7CB7E29945C33E2D', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E29945C33E2D FOREIGN KEY (moyen_attaque_id) REFERENCES moyen_attaque (id)'],
            ['all_data', 'FK_7CB7E299123C5581', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299123C5581 FOREIGN KEY (perpetrateur_id) REFERENCES perpetrateurs (id)'],
            ['all_data', 'FK_7CB7E299A6E44244', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)'],
            ['all_data', 'FK_7CB7E299A76ED395', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)'],
            ['all_data', 'FK_7CB7E299B6885C6C', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299B6885C6C FOREIGN KEY (espace_id) REFERENCES espace (id)'],
            ['attaque', 'FK_95751B92A76ED395', 'ALTER TABLE attaque ADD CONSTRAINT FK_95751B92A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)'],
            ['cible', 'FK_E15DEC3BA76ED395', 'ALTER TABLE cible ADD CONSTRAINT FK_E15DEC3BA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)'],
            ['espace', 'FK_6AB096DA76ED395', 'ALTER TABLE espace ADD CONSTRAINT FK_6AB096DA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)'],
            ['materiaux', 'FK_97C56625A76ED395', 'ALTER TABLE materiaux ADD CONSTRAINT FK_97C56625A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)'],
            ['materiel_attaque', 'FK_2D046047A76ED395', 'ALTER TABLE materiel_attaque ADD CONSTRAINT FK_2D046047A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)'],
            ['moyen_attaque', 'FK_BE72D329A76ED395', 'ALTER TABLE moyen_attaque ADD CONSTRAINT FK_BE72D329A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)'],
            ['pays', 'FK_349F3CAE98260155', 'ALTER TABLE pays ADD CONSTRAINT FK_349F3CAE98260155 FOREIGN KEY (region_id) REFERENCES region (id)'],
            ['perpetrateurs', 'FK_5552D9BFA76ED395', 'ALTER TABLE perpetrateurs ADD CONSTRAINT FK_5552D9BFA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)'],
        ];

        foreach ($fks as [$table, $name, $sql]) {
            $this->addSqlIf($this->tableExists($table) && !$this->foreignKeyExists($table, $name), $sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSqlIf($this->foreignKeyExists('users', 'FK_1483A5E9A6E44244'), 'ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E9A6E44244');
        $this->addSqlIf($this->foreignKeyExists('users', 'FK_1483A5E998260155'), 'ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E998260155');
        $this->addSqlIf($this->tableExists('users'), 'DROP TABLE `users`');
    }
}
