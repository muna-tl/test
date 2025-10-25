<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009150126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE doctor_profile ADD doctor_id VARCHAR(20) DEFAULT NULL');
        
        // Update existing records with unique doctor IDs
        $this->addSql("UPDATE doctor_profile SET doctor_id = CONCAT('DR', LPAD(id, 4, '0')) WHERE doctor_id IS NULL");
        
        // Now make it NOT NULL and add unique constraint
        $this->addSql('ALTER TABLE doctor_profile MODIFY doctor_id VARCHAR(20) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_12FAC9A287F4FB17 ON doctor_profile (doctor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_12FAC9A287F4FB17 ON doctor_profile');
        $this->addSql('ALTER TABLE doctor_profile DROP doctor_id');
    }
}
