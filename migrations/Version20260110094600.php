<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110094600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

   public function up(Schema $schema): void
{
    // colonnes restantes à synchroniser
    $this->addSql('ALTER TABLE emprunt CHANGE date_retour_effective date_retour_effective DATE DEFAULT NULL');
    $this->addSql('ALTER TABLE livre CHANGE image image VARCHAR(255) DEFAULT NULL');
    $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL, CHANGE profile profile VARCHAR(255) DEFAULT NULL');
    $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
}

public function down(Schema $schema): void
{
    // inverse (optionnel)
    $this->addSql('ALTER TABLE emprunt CHANGE date_retour_effective date_retour_effective DATE NOT NULL');
    $this->addSql('ALTER TABLE livre CHANGE image image VARCHAR(255) NOT NULL');
    $this->addSql('ALTER TABLE users CHANGE roles roles LONGTEXT NOT NULL COLLATE utf8mb4_bin, CHANGE profile profile VARCHAR(255) NOT NULL');
    $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME NOT NULL');
}
}
