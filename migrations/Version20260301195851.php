<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301195851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation ALTER rendez_vous_id SET NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous DROP CONSTRAINT fk_65e8aa0a62ff6cdf');
        $this->addSql('DROP INDEX idx_65e8aa0a62ff6cdf');
        $this->addSql('ALTER TABLE rendez_vous DROP consultation_id');
        $this->addSql('ALTER TABLE service_medical ALTER libelle TYPE VARCHAR(255)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B85F55C6A4D60759 ON service_medical (libelle)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation ALTER rendez_vous_id DROP NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD consultation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT fk_65e8aa0a62ff6cdf FOREIGN KEY (consultation_id) REFERENCES consultation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_65e8aa0a62ff6cdf ON rendez_vous (consultation_id)');
        $this->addSql('DROP INDEX UNIQ_B85F55C6A4D60759');
        $this->addSql('ALTER TABLE service_medical ALTER libelle TYPE VARCHAR(150)');
    }
}
