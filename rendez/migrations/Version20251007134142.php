<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251007134142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, note VARCHAR(255) DEFAULT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, INDEX IDX_FE38F8446B899279 (patient_id), INDEX IDX_FE38F84487F4FB17 (doctor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE doctor_profile (id INT AUTO_INCREMENT NOT NULL, specialty VARCHAR(150) NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_12FAC9A2A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8446B899279 FOREIGN KEY (patient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84487F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor_profile (id)');
        $this->addSql('ALTER TABLE doctor_profile ADD CONSTRAINT FK_12FAC9A2A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8446B899279');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84487F4FB17');
        $this->addSql('ALTER TABLE doctor_profile DROP FOREIGN KEY FK_12FAC9A2A76ED395');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE doctor_profile');
    }
}
