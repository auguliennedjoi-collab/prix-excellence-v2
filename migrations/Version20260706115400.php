<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260706115400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE critere ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE critere ADD CONSTRAINT FK_7F6A8053727ACA70 FOREIGN KEY (parent_id) REFERENCES critere (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7F6A8053727ACA70 ON critere (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE critere DROP FOREIGN KEY FK_7F6A8053727ACA70');
        $this->addSql('DROP INDEX IDX_7F6A8053727ACA70 ON critere');
        $this->addSql('ALTER TABLE critere DROP parent_id');
    }
}
