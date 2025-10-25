<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013143323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE specialty (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(500) DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_E066A6EC5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE doctor_profile ADD specialty_entity_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor_profile ADD CONSTRAINT FK_12FAC9A263FDC5F2 FOREIGN KEY (specialty_entity_id) REFERENCES specialty (id)');
        $this->addSql('CREATE INDEX IDX_12FAC9A263FDC5F2 ON doctor_profile (specialty_entity_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE specialty');
        $this->addSql('ALTER TABLE doctor_profile DROP FOREIGN KEY FK_12FAC9A263FDC5F2');
        $this->addSql('DROP INDEX IDX_12FAC9A263FDC5F2 ON doctor_profile');
        $this->addSql('ALTER TABLE doctor_profile DROP specialty_entity_id');
    }
}
