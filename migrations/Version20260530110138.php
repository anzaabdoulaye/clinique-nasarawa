<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260530110138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tarif_prestation ADD medicament_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tarif_prestation ADD CONSTRAINT FK_258A4FAB0D61F7 FOREIGN KEY (medicament_id) REFERENCES medicament (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_258A4FAB0D61F7 ON tarif_prestation (medicament_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tarif_prestation DROP CONSTRAINT FK_258A4FAB0D61F7');
        $this->addSql('DROP INDEX IDX_258A4FAB0D61F7');
        $this->addSql('ALTER TABLE tarif_prestation DROP medicament_id');
    }
}
