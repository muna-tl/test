<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251030132437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la nouvelle colonne specialty_id
        $this->addSql('ALTER TABLE doctor_profile ADD specialty_id INT DEFAULT NULL');
        
        // Migrer les données: créer les spécialités manquantes
        $this->addSql("
            INSERT IGNORE INTO specialty (name, description, is_active, created_at)
            SELECT DISTINCT dp.specialty, '', 1, NOW()
            FROM doctor_profile dp
            WHERE dp.specialty IS NOT NULL
        ");
        
        // Lier les docteurs aux spécialités
        $this->addSql("
            UPDATE doctor_profile dp
            INNER JOIN specialty s ON s.name = dp.specialty
            SET dp.specialty_id = s.id
            WHERE dp.specialty IS NOT NULL
        ");
        
        // Supprimer l'ancienne clé étrangère specialty_entity_id si elle existe
        $this->addSql('ALTER TABLE doctor_profile DROP FOREIGN KEY FK_12FAC9A263FDC5F2');
        $this->addSql('DROP INDEX IDX_12FAC9A263FDC5F2 ON doctor_profile');
        $this->addSql('ALTER TABLE doctor_profile DROP COLUMN specialty_entity_id');
        
        // Supprimer l'ancienne colonne specialty
        $this->addSql('ALTER TABLE doctor_profile DROP COLUMN specialty');
        
        // Rendre specialty_id obligatoire
        $this->addSql('ALTER TABLE doctor_profile MODIFY specialty_id INT NOT NULL');
        
        // Ajouter la contrainte de clé étrangère
        $this->addSql('ALTER TABLE doctor_profile ADD CONSTRAINT FK_12FAC9A29A353316 FOREIGN KEY (specialty_id) REFERENCES specialty (id)');
        $this->addSql('CREATE INDEX IDX_12FAC9A29A353316 ON doctor_profile (specialty_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE doctor_profile DROP FOREIGN KEY FK_12FAC9A29A353316');
        $this->addSql('DROP INDEX IDX_12FAC9A29A353316 ON doctor_profile');
        $this->addSql('ALTER TABLE doctor_profile ADD specialty VARCHAR(150) NOT NULL, ADD specialty_entity_id INT DEFAULT NULL, DROP specialty_id');
        $this->addSql('ALTER TABLE doctor_profile ADD CONSTRAINT `FK_12FAC9A263FDC5F2` FOREIGN KEY (specialty_entity_id) REFERENCES specialty (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_12FAC9A263FDC5F2 ON doctor_profile (specialty_entity_id)');
    }
}
