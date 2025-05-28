<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528094401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE path (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, coordinates JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sentier (id INT AUTO_INCREMENT NOT NULL, chemin_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, author_id VARCHAR(255) NOT NULL, auteur VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, position JSON DEFAULT NULL, path_length INT DEFAULT NULL, occurrences_count INT DEFAULT NULL, details VARCHAR(255) DEFAULT NULL, pmr INT DEFAULT NULL, meilleures_saisons JSON DEFAULT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, date_suppression DATETIME DEFAULT NULL, date_publication DATETIME DEFAULT NULL, nb_taxons INT DEFAULT NULL, UNIQUE INDEX UNIQ_3B0D0A9B3BD6E429 (chemin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sentier ADD CONSTRAINT FK_3B0D0A9B3BD6E429 FOREIGN KEY (chemin_id) REFERENCES path (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sentier DROP FOREIGN KEY FK_3B0D0A9B3BD6E429');
        $this->addSql('DROP TABLE path');
        $this->addSql('DROP TABLE sentier');
    }
}
