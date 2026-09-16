<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'aura disparaît : les notifications ne rapportent plus de points.
 */
final class Version20260916120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime les points et le message d\'aura des notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media DROP aura_points');
        $this->addSql('ALTER TABLE media DROP aura_message');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media ADD aura_points INT DEFAULT 100 NOT NULL');
        $this->addSql('ALTER TABLE media ADD aura_message TEXT DEFAULT NULL');
    }
}
