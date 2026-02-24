<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224102533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_medical DROP CONSTRAINT fk_3581ee626b899279');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT FK_3581EE626B899279 FOREIGN KEY (patient_id) REFERENCES patient (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE patient ALTER date_naissance DROP NOT NULL');
        $this->addSql('ALTER TABLE patient ALTER code TYPE VARCHAR(30)');
        $this->addSql('ALTER TABLE patient ALTER sexe TYPE VARCHAR(10)');
        $this->addSql('ALTER TABLE patient ALTER sexe SET NOT NULL');
        $this->addSql('ALTER TABLE utilisateur DROP CONSTRAINT fk_1d1c63b3cf7bca0e');
        $this->addSql('ALTER TABLE utilisateur ALTER statut TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utilisateur ALTER service_medical_id DROP NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME COLUMN hash_password TO password');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3CF7BCA0E FOREIGN KEY (service_medical_id) REFERENCES service_medical (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_medical DROP CONSTRAINT FK_3581EE626B899279');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT fk_3581ee626b899279 FOREIGN KEY (patient_id) REFERENCES patient (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient ALTER date_naissance SET NOT NULL');
        $this->addSql('ALTER TABLE patient ALTER code TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE patient ALTER sexe TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE patient ALTER sexe DROP NOT NULL');
        $this->addSql('ALTER TABLE utilisateur DROP CONSTRAINT FK_1D1C63B3CF7BCA0E');
        $this->addSql('ALTER TABLE utilisateur ALTER statut TYPE BOOLEAN');
        $this->addSql('ALTER TABLE utilisateur ALTER service_medical_id SET NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME COLUMN password TO hash_password');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT fk_1d1c63b3cf7bca0e FOREIGN KEY (service_medical_id) REFERENCES service_medical (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
