<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530135331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, occurrence_id INT DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, author VARCHAR(255) DEFAULT NULL, user_id VARCHAR(255) DEFAULT NULL, INDEX IDX_C53D045F30572FAC (occurrence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE occurrence (id INT AUTO_INCREMENT NOT NULL, sentier_id INT DEFAULT NULL, card_tag VARCHAR(255) DEFAULT NULL, position JSON DEFAULT NULL, anecdotes LONGTEXT DEFAULT NULL, user_id VARCHAR(255) DEFAULT NULL, date_suppression DATETIME DEFAULT NULL, taxon JSON DEFAULT NULL, INDEX IDX_BEFD81F31359062D (sentier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F30572FAC FOREIGN KEY (occurrence_id) REFERENCES occurrence (id)');
        $this->addSql('ALTER TABLE occurrence ADD CONSTRAINT FK_BEFD81F31359062D FOREIGN KEY (sentier_id) REFERENCES sentier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F30572FAC');
        $this->addSql('ALTER TABLE occurrence DROP FOREIGN KEY FK_BEFD81F31359062D');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE occurrence');
    }
}
