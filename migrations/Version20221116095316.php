<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221116095316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ping ADD isLogged TINYINT(1) NOT NULL, ADD isLocated TINYINT(1) NOT NULL, ADD isCloseToTrail TINYINT(1) NOT NULL, ADD isOnline TINYINT(1) NOT NULL, DROP is_logged, DROP is_located, DROP is_close_to_trail, DROP is_online, CHANGE date date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ping ADD is_logged TINYINT(1) NOT NULL, ADD is_located TINYINT(1) NOT NULL, ADD is_close_to_trail TINYINT(1) NOT NULL, ADD is_online TINYINT(1) NOT NULL, DROP isLogged, DROP isLocated, DROP isCloseToTrail, DROP isOnline, CHANGE date date DATETIME NOT NULL');
    }
}
