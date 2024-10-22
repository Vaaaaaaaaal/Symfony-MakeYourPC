<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionXXXXXXXXXXXXXX extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_admin column to Users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Users ADD is_admin TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Users DROP COLUMN is_admin');
    }
}
