<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929114749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE envelope envelope VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE scenario_transaction CHANGE action action VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type VARCHAR(20) NOT NULL, CHANGE is_recurring is_recurring TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE envelope envelope VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE scenario_transaction CHANGE action action VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type VARCHAR(255) NOT NULL, CHANGE is_recurring is_recurring TINYINT NOT NULL');
    }
}
