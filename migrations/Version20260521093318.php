<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521093318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_medical ADD antecedents JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier_medical DROP antecedents_medicaux');
        $this->addSql('DROP INDEX uniq_3e840f22f531f4c5');
        $this->addSql('ALTER TABLE examen_clinique ADD date_prise TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('CREATE INDEX IDX_3E840F22F531F4C5 ON examen_clinique (hospitalisation_id)');
        $this->addSql('ALTER TABLE hospitalisation ADD bilan_paraclinique JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE hospitalisation ADD hypotheses_diagnostiques TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE hospitalisation ADD diagnostic_positif TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_medical ADD antecedents_medicaux TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier_medical DROP antecedents');
        $this->addSql('DROP INDEX IDX_3E840F22F531F4C5');
        $this->addSql('ALTER TABLE examen_clinique DROP date_prise');
        $this->addSql('CREATE UNIQUE INDEX uniq_3e840f22f531f4c5 ON examen_clinique (hospitalisation_id)');
        $this->addSql('ALTER TABLE hospitalisation DROP bilan_paraclinique');
        $this->addSql('ALTER TABLE hospitalisation DROP hypotheses_diagnostiques');
        $this->addSql('ALTER TABLE hospitalisation DROP diagnostic_positif');
    }
}
