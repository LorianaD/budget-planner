<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929092630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account ADD initial_balance NUMERIC(10, 2) NOT NULL, ADD household_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A4A848132E FOREIGN KEY (household_id_id) REFERENCES household (id)');
        $this->addSql('CREATE INDEX IDX_7D3656A4A848132E ON account (household_id_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP FOREIGN KEY FK_7D3656A4A848132E');
        $this->addSql('DROP INDEX IDX_7D3656A4A848132E ON account');
        $this->addSql('ALTER TABLE account DROP initial_balance, DROP household_id_id');
    }
}
