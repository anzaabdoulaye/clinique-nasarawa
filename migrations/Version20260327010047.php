<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327010047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_medical DROP groupe_sanguin');
        $this->addSql('ALTER TABLE dossier_medical DROP allergies');
        $this->addSql('ALTER TABLE dossier_medical DROP antecedents_medicaux');
        $this->addSql('ALTER TABLE dossier_medical DROP antecedents_chirurgicaux');
        $this->addSql('ALTER TABLE dossier_medical DROP maladies_chroniques');
        $this->addSql('ALTER TABLE dossier_medical DROP traitement_en_cours');
        $this->addSql('ALTER TABLE dossier_medical DROP handicap');
        $this->addSql('ALTER TABLE dossier_medical DROP grossesse');
    }
}
