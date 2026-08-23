<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Supprimer une notification déjà consultée butait sur la clé étrangère de
 * media_access : les traces d'un média s'en vont désormais avec lui.
 */
final class Version20260823100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime en cascade les consultations et coups de pouce d\'une notification effacée';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media_access DROP CONSTRAINT fk_ba10b18aea9fdd75');
        $this->addSql('ALTER TABLE media_access ADD CONSTRAINT fk_ba10b18aea9fdd75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE feed_skip DROP CONSTRAINT fk_78e1be2aea9fdd75');
        $this->addSql('ALTER TABLE feed_skip ADD CONSTRAINT fk_78e1be2aea9fdd75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media_access DROP CONSTRAINT fk_ba10b18aea9fdd75');
        $this->addSql('ALTER TABLE media_access ADD CONSTRAINT fk_ba10b18aea9fdd75 FOREIGN KEY (media_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE feed_skip DROP CONSTRAINT fk_78e1be2aea9fdd75');
        $this->addSql('ALTER TABLE feed_skip ADD CONSTRAINT fk_78e1be2aea9fdd75 FOREIGN KEY (media_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
