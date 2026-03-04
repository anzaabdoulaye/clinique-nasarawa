<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303135505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE patient ADD emergency_contact_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD emergency_contact_relation VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD emergency_contact_phone VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD emergency_contact_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD taille DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD poids DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD allergies TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD antecedents_medicaux TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD antecedents_chirurgicaux TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD maladies_chroniques TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD traitement_en_cours TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD handicap VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD grossesse BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE patient DROP emergency_contact_name');
        $this->addSql('ALTER TABLE patient DROP emergency_contact_relation');
        $this->addSql('ALTER TABLE patient DROP emergency_contact_phone');
        $this->addSql('ALTER TABLE patient DROP emergency_contact_address');
        $this->addSql('ALTER TABLE patient DROP taille');
        $this->addSql('ALTER TABLE patient DROP poids');
        $this->addSql('ALTER TABLE patient DROP allergies');
        $this->addSql('ALTER TABLE patient DROP antecedents_medicaux');
        $this->addSql('ALTER TABLE patient DROP antecedents_chirurgicaux');
        $this->addSql('ALTER TABLE patient DROP maladies_chroniques');
        $this->addSql('ALTER TABLE patient DROP traitement_en_cours');
        $this->addSql('ALTER TABLE patient DROP handicap');
        $this->addSql('ALTER TABLE patient DROP grossesse');
    }
}
