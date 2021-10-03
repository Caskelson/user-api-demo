<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211001141101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_7BA2F5EBA76ED395');
        $this->addSql('CREATE TEMPORARY TABLE __temp__api_token AS SELECT id, user_id, token, expires_at FROM api_token');
        $this->addSql('DROP TABLE api_token');
        $this->addSql('CREATE TABLE api_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, token VARCHAR(255) NOT NULL COLLATE BINARY, expires_at DATETIME NOT NULL, CONSTRAINT FK_7BA2F5EBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO api_token (id, user_id, token, expires_at) SELECT id, user_id, token, expires_at FROM __temp__api_token');
        $this->addSql('DROP TABLE __temp__api_token');
        $this->addSql('CREATE INDEX IDX_7BA2F5EBA76ED395 ON api_token (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_7BA2F5EBA76ED395');
        $this->addSql('CREATE TEMPORARY TABLE __temp__api_token AS SELECT id, user_id, token, expires_at FROM api_token');
        $this->addSql('DROP TABLE api_token');
        $this->addSql('CREATE TABLE api_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO api_token (id, user_id, token, expires_at) SELECT id, user_id, token, expires_at FROM __temp__api_token');
        $this->addSql('DROP TABLE __temp__api_token');
        $this->addSql('CREATE INDEX IDX_7BA2F5EBA76ED395 ON api_token (user_id)');
    }
}
