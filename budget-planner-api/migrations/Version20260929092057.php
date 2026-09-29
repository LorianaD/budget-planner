<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929092057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE household_member (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, user_id_id INT NOT NULL, household_id_id INT NOT NULL, INDEX IDX_59EA3F6D9D86650F (user_id_id), INDEX IDX_59EA3F6DA848132E (household_id_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE household_member ADD CONSTRAINT FK_59EA3F6D9D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE household_member ADD CONSTRAINT FK_59EA3F6DA848132E FOREIGN KEY (household_id_id) REFERENCES household (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE household_member DROP FOREIGN KEY FK_59EA3F6D9D86650F');
        $this->addSql('ALTER TABLE household_member DROP FOREIGN KEY FK_59EA3F6DA848132E');
        $this->addSql('DROP TABLE household_member');
    }
}
