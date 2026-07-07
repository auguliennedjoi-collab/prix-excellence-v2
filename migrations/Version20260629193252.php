<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260629193252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE parametre_page (id INT AUTO_INCREMENT NOT NULL, date_debut_etude DATE NOT NULL, date_fin_etude DATE NOT NULL, date_debut_preselection DATE NOT NULL, date_fin_preselection DATE NOT NULL, date_debut_audition DATE NOT NULL, date_fin_audition DATE NOT NULL, date_debut_proclamation DATE NOT NULL, date_fin_proclamation DATE NOT NULL, qui_peut_participer LONGTEXT NOT NULL, dossier_requis JSON NOT NULL, recompenses JSON NOT NULL, footer_texte VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE parametre_page');
    }
}
