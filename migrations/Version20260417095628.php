<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417095628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation ADD frequence_respiratoire INT DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD temperature DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD tension_arterielle VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD frequence_cardiaque INT DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD frequence_respiratoire INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation DROP frequence_respiratoire');
        $this->addSql('ALTER TABLE patient DROP temperature');
        $this->addSql('ALTER TABLE patient DROP tension_arterielle');
        $this->addSql('ALTER TABLE patient DROP frequence_cardiaque');
        $this->addSql('ALTER TABLE patient DROP frequence_respiratoire');
    }
}
