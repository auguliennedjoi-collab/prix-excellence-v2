<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260709074236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature ADD taux_plagiat DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE evaluation DROP note_avant_plagiat');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature DROP taux_plagiat');
        $this->addSql('ALTER TABLE evaluation ADD note_avant_plagiat DOUBLE PRECISION DEFAULT NULL');
    }
}
