<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260822212956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Le DEFAULT est indispensable : les médias déjà en base doivent recevoir
        // l'aura standard, sinon l'ALTER échoue sur une table non vide.
        $this->addSql('ALTER TABLE media ADD aura_points INT DEFAULT 100 NOT NULL');
        $this->addSql('ALTER TABLE media ADD aura_message TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD display_name VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD avatar VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media DROP aura_points');
        $this->addSql('ALTER TABLE media DROP aura_message');
        $this->addSql('ALTER TABLE "user" DROP display_name');
        $this->addSql('ALTER TABLE "user" DROP avatar');
    }
}
