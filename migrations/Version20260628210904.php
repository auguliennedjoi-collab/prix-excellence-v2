<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628210904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidat (id BINARY(16) NOT NULL, nom VARCHAR(255) NOT NULL, prenoms VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(24) NOT NULL, niveau_etude VARCHAR(255) NOT NULL, code_suivi VARCHAR(50) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidature (id BINARY(16) NOT NULL, date_soumission DATETIME NOT NULL, statut_demande VARCHAR(100) NOT NULL, statut_traitement VARCHAR(100) NOT NULL, candidat_id BINARY(16) NOT NULL, edition_id INT DEFAULT NULL, INDEX IDX_E33BD3B88D0EB82 (candidat_id), INDEX IDX_E33BD3B874281A5E (edition_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(200) NOT NULL, type VARCHAR(100) NOT NULL, chemin VARCHAR(255) NOT NULL, candidat_id BINARY(16) NOT NULL, candidature_id BINARY(16) NOT NULL, INDEX IDX_D8698A768D0EB82 (candidat_id), INDEX IDX_D8698A76B6121583 (candidature_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE edition (id INT AUTO_INCREMENT NOT NULL, annee INT NOT NULL, theme VARCHAR(255) DEFAULT NULL, edition INT NOT NULL, date_lancement DATETIME NOT NULL, date_cloture DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id BINARY(16) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) DEFAULT NULL, prenoms VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, actif TINYINT DEFAULT NULL, last_login DATETIME DEFAULT NULL, titre VARCHAR(255) DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, is_verified TINYINT NOT NULL, password_change_required TINYINT NOT NULL, must_change_password TINYINT NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B88D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B874281A5E FOREIGN KEY (edition_id) REFERENCES edition (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A768D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B88D0EB82');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B874281A5E');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A768D0EB82');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76B6121583');
        $this->addSql('DROP TABLE candidat');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE edition');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
