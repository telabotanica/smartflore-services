<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250828064511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE eFloreRedaction_triples');
        $this->addSql('DROP TABLE eFloreRedaction_pages');
        $this->addSql('ALTER TABLE sentier ADD ancien_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE eFloreRedaction_triples (id INT UNSIGNED AUTO_INCREMENT NOT NULL, resource VARCHAR(255) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, property VARCHAR(255) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, value TEXT CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, INDEX resource (resource), INDEX property (property), PRIMARY KEY(id)) DEFAULT CHARACTER SET latin1 COLLATE `latin1_swedish_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE eFloreRedaction_pages (id INT UNSIGNED AUTO_INCREMENT NOT NULL, tag VARCHAR(50) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, time DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, body TEXT CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, body_r TEXT CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, owner VARCHAR(50) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, user VARCHAR(50) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, latest VARCHAR(255) CHARACTER SET latin1 DEFAULT \'N\' NOT NULL COLLATE `latin1_swedish_ci`, handler VARCHAR(30) CHARACTER SET latin1 DEFAULT \'page\' NOT NULL COLLATE `latin1_swedish_ci`, comment_on VARCHAR(50) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, FULLTEXT INDEX tag (tag, body), INDEX idx_time (time), INDEX idx_tag (tag), INDEX idx_latest (latest), INDEX idx_comment_on (comment_on), PRIMARY KEY(id)) DEFAULT CHARACTER SET latin1 COLLATE `latin1_swedish_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sentier DROP ancien_id');
    }
}
