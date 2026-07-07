<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260703114104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE critere (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, note_max DOUBLE PRECISION NOT NULL, ordre INT NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE evaluation (id BINARY(16) NOT NULL, date_evaluation DATETIME NOT NULL, note_ecrite DOUBLE PRECISION DEFAULT NULL, note_orale DOUBLE PRECISION DEFAULT NULL, taux_plagiat DOUBLE PRECISION DEFAULT NULL, note_avant_plagiat DOUBLE PRECISION DEFAULT NULL, note_finale DOUBLE PRECISION DEFAULT NULL, jury_id BINARY(16) NOT NULL, candidature_id BINARY(16) NOT NULL, INDEX IDX_1323A575E560103C (jury_id), INDEX IDX_1323A575B6121583 (candidature_id), UNIQUE INDEX uniq_jury_candidature (jury_id, candidature_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE note_critere (id INT AUTO_INCREMENT NOT NULL, note DOUBLE PRECISION NOT NULL, evaluation_id BINARY(16) NOT NULL, critere_id INT NOT NULL, INDEX IDX_FECC44C9456C5646 (evaluation_id), INDEX IDX_FECC44C99E5F45AB (critere_id), UNIQUE INDEX uniq_evaluation_critere (evaluation_id, critere_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575E560103C FOREIGN KEY (jury_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id)');
        $this->addSql('ALTER TABLE note_critere ADD CONSTRAINT FK_FECC44C9456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)');
        $this->addSql('ALTER TABLE note_critere ADD CONSTRAINT FK_FECC44C99E5F45AB FOREIGN KEY (critere_id) REFERENCES critere (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575E560103C');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575B6121583');
        $this->addSql('ALTER TABLE note_critere DROP FOREIGN KEY FK_FECC44C9456C5646');
        $this->addSql('ALTER TABLE note_critere DROP FOREIGN KEY FK_FECC44C99E5F45AB');
        $this->addSql('DROP TABLE critere');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE note_critere');
    }
}
