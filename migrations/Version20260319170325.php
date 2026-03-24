<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319170325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture ALTER montant_total DROP NOT NULL');
        $this->addSql('ALTER TABLE facture ALTER montant_paye DROP NOT NULL');
        $this->addSql('ALTER TABLE facture ALTER reste_apayer DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture ALTER montant_total SET NOT NULL');
        $this->addSql('ALTER TABLE facture ALTER montant_paye SET NOT NULL');
        $this->addSql('ALTER TABLE facture ALTER reste_apayer SET NOT NULL');
    }
}
