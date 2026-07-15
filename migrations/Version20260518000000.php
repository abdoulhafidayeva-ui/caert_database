<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\IdempotentMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Schéma de base AUCTC — compatible installation sur base vide.
 * Crée les tables métier absentes ; ne recrée rien si elles existent déjà.
 */
final class Version20260518000000 extends AbstractMigration
{
    use IdempotentMigrationTrait;

    public function getDescription(): string
    {
        return 'Baseline schema: region, pays, référentiels, all_data, app_param, roles (base vide)';
    }

    public function up(Schema $schema): void
    {
        $this->addSqlIf(!$this->tableExists('region'), <<<'SQL'
CREATE TABLE region (
    id INT AUTO_INCREMENT NOT NULL,
    libelle VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        $this->addSqlIf(!$this->tableExists('pays'), <<<'SQL'
CREATE TABLE pays (
    id INT AUTO_INCREMENT NOT NULL,
    region_id INT DEFAULT NULL,
    libelle VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    capitale VARCHAR(255) NOT NULL,
    INDEX IDX_349F3CAE98260155 (region_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        $this->addSqlIf(
            $this->tableExists('pays') && !$this->foreignKeyExists('pays', 'FK_349F3CAE98260155'),
            'ALTER TABLE pays ADD CONSTRAINT FK_349F3CAE98260155 FOREIGN KEY (region_id) REFERENCES region (id)'
        );

        foreach ([
            'attaque' => 'attaque',
            'cible' => 'cible',
            'espace' => 'espace',
            'materiaux' => 'materiaux',
            'materiel_attaque' => 'materiel_attaque',
            'moyen_attaque' => 'moyen_attaque',
            'perpetrateurs' => 'perpetrateurs',
        ] as $table) {
            $this->addSqlIf(!$this->tableExists($table), <<<SQL
CREATE TABLE {$table} (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    libelle VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY (libelle),
    INDEX (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);
        }

        $this->addSqlIf(!$this->tableExists('all_data'), <<<'SQL'
CREATE TABLE all_data (
    id INT AUTO_INCREMENT NOT NULL,
    attaque_id INT DEFAULT NULL,
    materiel_attaque_id INT DEFAULT NULL,
    cible_id INT DEFAULT NULL,
    materieaux_id INT DEFAULT NULL,
    moyen_attaque_id INT DEFAULT NULL,
    perpetrateur_id INT DEFAULT NULL,
    pays_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    espace_id INT DEFAULT NULL,
    details VARCHAR(255) NOT NULL,
    mort_securite_militaire INT DEFAULT NULL,
    mort_civil INT NOT NULL,
    mort_terroriste INT NOT NULL,
    disparu_securite_militaire INT DEFAULT NULL,
    disparu_civil INT DEFAULT NULL,
    disparu_terroriste INT DEFAULT NULL,
    blesse_securite_militaire INT NOT NULL,
    blesse_civil INT NOT NULL,
    blesse_terroriste INT NOT NULL,
    total_deces INT NOT NULL,
    total_disparus INT DEFAULT NULL,
    total_blesses INT NOT NULL,
    otages INT NOT NULL,
    liberes INT NOT NULL,
    terroriste_arretes INT NOT NULL,
    autres VARCHAR(255) NOT NULL,
    remarque VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    date_attaque DATETIME DEFAULT NULL,
    localite VARCHAR(255) DEFAULT NULL,
    is_published TINYINT(1) DEFAULT NULL,
    objet_rejet VARCHAR(255) DEFAULT NULL,
    INDEX IDX_7CB7E299118FE712 (attaque_id),
    INDEX IDX_7CB7E2997104F1E6 (materiel_attaque_id),
    INDEX IDX_7CB7E299A96E5E09 (cible_id),
    INDEX IDX_7CB7E29981072610 (materieaux_id),
    INDEX IDX_7CB7E29945C33E2D (moyen_attaque_id),
    INDEX IDX_7CB7E299123C5581 (perpetrateur_id),
    INDEX IDX_7CB7E299A6E44244 (pays_id),
    INDEX IDX_7CB7E299A76ED395 (user_id),
    INDEX IDX_7CB7E299B6885C6C (espace_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        $this->addSqlIf(!$this->tableExists('app_param'), <<<'SQL'
CREATE TABLE app_param (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    site_url VARCHAR(255) DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        $this->addSqlIf(!$this->tableExists('roles'), <<<'SQL'
CREATE TABLE roles (
    id INT AUTO_INCREMENT NOT NULL,
    label VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    deleted TINYINT(1) DEFAULT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        // FK métier (sans users — créé ensuite)
        $fks = [
            ['all_data', 'FK_7CB7E299118FE712', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299118FE712 FOREIGN KEY (attaque_id) REFERENCES attaque (id)'],
            ['all_data', 'FK_7CB7E2997104F1E6', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E2997104F1E6 FOREIGN KEY (materiel_attaque_id) REFERENCES materiel_attaque (id)'],
            ['all_data', 'FK_7CB7E299A96E5E09', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299A96E5E09 FOREIGN KEY (cible_id) REFERENCES cible (id)'],
            ['all_data', 'FK_7CB7E29981072610', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E29981072610 FOREIGN KEY (materieaux_id) REFERENCES materiaux (id)'],
            ['all_data', 'FK_7CB7E29945C33E2D', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E29945C33E2D FOREIGN KEY (moyen_attaque_id) REFERENCES moyen_attaque (id)'],
            ['all_data', 'FK_7CB7E299123C5581', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299123C5581 FOREIGN KEY (perpetrateur_id) REFERENCES perpetrateurs (id)'],
            ['all_data', 'FK_7CB7E299A6E44244', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)'],
            ['all_data', 'FK_7CB7E299B6885C6C', 'ALTER TABLE all_data ADD CONSTRAINT FK_7CB7E299B6885C6C FOREIGN KEY (espace_id) REFERENCES espace (id)'],
        ];

        foreach ($fks as [$table, $name, $sql]) {
            $this->addSqlIf($this->tableExists($table) && !$this->foreignKeyExists($table, $name), $sql);
        }
    }

    public function down(Schema $schema): void
    {
        // Baseline volontairement non destructif en down (évite DROP massif en cascade)
        $this->write('Down skipped for baseline — drop database manually if needed.');
    }
}
