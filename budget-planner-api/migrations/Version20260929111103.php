<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929111103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scenario_transaction (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, action VARCHAR(20) DEFAULT NULL, scenario_id INT NOT NULL, transaction_id INT DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_F330EEACE04E49DF (scenario_id), INDEX IDX_F330EEAC2FC0CB0F (transaction_id), INDEX IDX_F330EEAC12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE scenario_transaction ADD CONSTRAINT FK_F330EEACE04E49DF FOREIGN KEY (scenario_id) REFERENCES scenario (id)');
        $this->addSql('ALTER TABLE scenario_transaction ADD CONSTRAINT FK_F330EEAC2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('ALTER TABLE scenario_transaction ADD CONSTRAINT FK_F330EEAC12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scenario_transaction DROP FOREIGN KEY FK_F330EEACE04E49DF');
        $this->addSql('ALTER TABLE scenario_transaction DROP FOREIGN KEY FK_F330EEAC2FC0CB0F');
        $this->addSql('ALTER TABLE scenario_transaction DROP FOREIGN KEY FK_F330EEAC12469DE2');
        $this->addSql('DROP TABLE scenario_transaction');
    }
}
