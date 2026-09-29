<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929110458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scenario (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, base_period DATE NOT NULL, household_id INT NOT NULL, INDEX IDX_3E45C8D8E79FF843 (household_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE scenario ADD CONSTRAINT FK_3E45C8D8E79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scenario DROP FOREIGN KEY FK_3E45C8D8E79FF843');
        $this->addSql('DROP TABLE scenario');
    }
}
