<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015134817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Check if column exists before adding
        $this->addSql('SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "appointment" AND COLUMN_NAME = "confirmation_code")');
        
        // Add column as nullable first only if it doesn't exist
        $this->addSql('SET @sql = IF(@column_exists = 0, "ALTER TABLE appointment ADD confirmation_code VARCHAR(20) DEFAULT NULL", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        // Update status column
        $this->addSql('ALTER TABLE appointment CHANGE status status VARCHAR(50) DEFAULT \'confirmed\' NOT NULL');
        
        // Generate unique confirmation codes for existing appointments without codes
        $this->addSql('UPDATE appointment SET confirmation_code = UPPER(CONCAT(SUBSTRING(MD5(CONCAT(id, RAND())), 1, 4), \'-\', SUBSTRING(MD5(CONCAT(id, RAND() * 2)), 1, 4))) WHERE confirmation_code IS NULL OR confirmation_code = \'\'');
        
        // Make column NOT NULL
        $this->addSql('ALTER TABLE appointment MODIFY confirmation_code VARCHAR(20) NOT NULL');
        
        // Add unique index if it doesn't exist
        $this->addSql('SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "appointment" AND INDEX_NAME = "UNIQ_FE38F844A0E239DE")');
        $this->addSql('SET @sql = IF(@index_exists = 0, "CREATE UNIQUE INDEX UNIQ_FE38F844A0E239DE ON appointment (confirmation_code)", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_FE38F844A0E239DE ON appointment');
        $this->addSql('ALTER TABLE appointment DROP confirmation_code, CHANGE status status VARCHAR(50) DEFAULT \'pending\' NOT NULL');
    }
}
