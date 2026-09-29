<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929093735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP FOREIGN KEY `FK_7D3656A4A848132E`');
        $this->addSql('DROP INDEX IDX_7D3656A4A848132E ON account');
        $this->addSql('ALTER TABLE account CHANGE name name VARCHAR(100) NOT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE initial_balance initial_balance NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE household_id_id household_id INT NOT NULL');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A4E79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('CREATE INDEX IDX_7D3656A4E79FF843 ON account (household_id)');
        $this->addSql('ALTER TABLE household CHANGE name name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE household_member DROP FOREIGN KEY `FK_59EA3F6D9D86650F`');
        $this->addSql('ALTER TABLE household_member DROP FOREIGN KEY `FK_59EA3F6DA848132E`');
        $this->addSql('DROP INDEX IDX_59EA3F6D9D86650F ON household_member');
        $this->addSql('DROP INDEX IDX_59EA3F6DA848132E ON household_member');
        $this->addSql('ALTER TABLE household_member ADD user_id INT NOT NULL, ADD household_id INT NOT NULL, DROP user_id_id, DROP household_id_id');
        $this->addSql('ALTER TABLE household_member ADD CONSTRAINT FK_59EA3F6DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE household_member ADD CONSTRAINT FK_59EA3F6DE79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('CREATE INDEX IDX_59EA3F6DA76ED395 ON household_member (user_id)');
        $this->addSql('CREATE INDEX IDX_59EA3F6DE79FF843 ON household_member (household_id)');
        $this->addSql('ALTER TABLE user CHANGE name name VARCHAR(200) NOT NULL, CHANGE colour colour VARCHAR(7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP FOREIGN KEY FK_7D3656A4E79FF843');
        $this->addSql('DROP INDEX IDX_7D3656A4E79FF843 ON account');
        $this->addSql('ALTER TABLE account CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE initial_balance initial_balance NUMERIC(10, 2) NOT NULL, CHANGE household_id household_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT `FK_7D3656A4A848132E` FOREIGN KEY (household_id_id) REFERENCES household (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_7D3656A4A848132E ON account (household_id_id)');
        $this->addSql('ALTER TABLE household CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE household_member DROP FOREIGN KEY FK_59EA3F6DA76ED395');
        $this->addSql('ALTER TABLE household_member DROP FOREIGN KEY FK_59EA3F6DE79FF843');
        $this->addSql('DROP INDEX IDX_59EA3F6DA76ED395 ON household_member');
        $this->addSql('DROP INDEX IDX_59EA3F6DE79FF843 ON household_member');
        $this->addSql('ALTER TABLE household_member ADD user_id_id INT NOT NULL, ADD household_id_id INT NOT NULL, DROP user_id, DROP household_id');
        $this->addSql('ALTER TABLE household_member ADD CONSTRAINT `FK_59EA3F6D9D86650F` FOREIGN KEY (user_id_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE household_member ADD CONSTRAINT `FK_59EA3F6DA848132E` FOREIGN KEY (household_id_id) REFERENCES household (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_59EA3F6D9D86650F ON household_member (user_id_id)');
        $this->addSql('CREATE INDEX IDX_59EA3F6DA848132E ON household_member (household_id_id)');
        $this->addSql('ALTER TABLE user CHANGE name name VARCHAR(255) NOT NULL, CHANGE colour colour VARCHAR(255) DEFAULT NULL');
    }
}
