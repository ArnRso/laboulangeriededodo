<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260822130649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pack RENAME COLUMN unlock_delay_hours TO unlock_delay_minutes');
        // Les valeurs existantes sont exprimées en heures : sans cette
        // conversion, un pack réglé sur 24 h passerait à 24 minutes.
        $this->addSql('UPDATE pack SET unlock_delay_minutes = unlock_delay_minutes * 60');
    }

    public function down(Schema $schema): void
    {
        // Les délais de moins d'une heure ne sont pas représentables en heures :
        // ils sont ramenés à une heure plutôt que tronqués à zéro.
        $this->addSql('UPDATE pack SET unlock_delay_minutes = GREATEST(unlock_delay_minutes / 60, 1)');
        $this->addSql('ALTER TABLE pack RENAME COLUMN unlock_delay_minutes TO unlock_delay_hours');
    }
}
