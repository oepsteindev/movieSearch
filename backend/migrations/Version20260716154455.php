<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260716154455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favorite_items (id INT AUTO_INCREMENT NOT NULL, external_id VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(512) NOT NULL, year INT DEFAULT NULL, created_at DATETIME NOT NULL, favorite_list_id INT NOT NULL, INDEX IDX_5796C26960FAB8E5 (favorite_list_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE favorite_lists (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_BEAEB4817E3C61F9 (owner_id), UNIQUE INDEX uniq_owner_name (owner_id, name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE favorite_items ADD CONSTRAINT FK_5796C26960FAB8E5 FOREIGN KEY (favorite_list_id) REFERENCES favorite_lists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favorite_lists ADD CONSTRAINT FK_BEAEB4817E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favorite_items DROP FOREIGN KEY FK_5796C26960FAB8E5');
        $this->addSql('ALTER TABLE favorite_lists DROP FOREIGN KEY FK_BEAEB4817E3C61F9');
        $this->addSql('DROP TABLE favorite_items');
        $this->addSql('DROP TABLE favorite_lists');
        $this->addSql('DROP TABLE users');
    }
}
