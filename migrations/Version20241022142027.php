<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241022142027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Users DROP is_admin');
        $this->addSql('ALTER TABLE product ADD image VARCHAR(255) NOT NULL, ADD rating DOUBLE PRECISION NOT NULL, DROP description, DROP stock_quantity, CHANGE type type VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Users ADD is_admin TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE product ADD description LONGTEXT DEFAULT NULL, ADD stock_quantity INT NOT NULL, DROP image, DROP rating, CHANGE type type VARCHAR(100) DEFAULT NULL');
    }
}
