<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251103141128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE medical_document (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, file_path VARCHAR(255) NOT NULL, uploaded_at DATETIME NOT NULL, medical_record_id INT NOT NULL, uploaded_by_id INT NOT NULL, INDEX IDX_A4F36721B88E2BB6 (medical_record_id), INDEX IDX_A4F36721A2B28FE8 (uploaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE medical_record (id INT AUTO_INCREMENT NOT NULL, blood_type VARCHAR(10) DEFAULT NULL, allergies LONGTEXT DEFAULT NULL, chronic_diseases LONGTEXT DEFAULT NULL, current_medications LONGTEXT DEFAULT NULL, emergency_contact VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, patient_id INT NOT NULL, UNIQUE INDEX UNIQ_F06A283E6B899279 (patient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE medical_document ADD CONSTRAINT FK_A4F36721B88E2BB6 FOREIGN KEY (medical_record_id) REFERENCES medical_record (id)');
        $this->addSql('ALTER TABLE medical_document ADD CONSTRAINT FK_A4F36721A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE medical_record ADD CONSTRAINT FK_F06A283E6B899279 FOREIGN KEY (patient_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE medical_document DROP FOREIGN KEY FK_A4F36721B88E2BB6');
        $this->addSql('ALTER TABLE medical_document DROP FOREIGN KEY FK_A4F36721A2B28FE8');
        $this->addSql('ALTER TABLE medical_record DROP FOREIGN KEY FK_F06A283E6B899279');
        $this->addSql('DROP TABLE medical_document');
        $this->addSql('DROP TABLE medical_record');
    }
}
