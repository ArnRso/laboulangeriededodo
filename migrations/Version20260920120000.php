<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Des étiquettes de rangement sur les notifications, pour s'y retrouver dans
 * le back-office. Elles ne sortent jamais côté destinataire.
 *
 * Le retour arrière les abandonne : elles n'existent que pour le tri.
 */
final class Version20260920120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les étiquettes de rangement des notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE media ADD tags JSON DEFAULT '[]' NOT NULL");
        $this->addSql('ALTER TABLE media ALTER tags DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media DROP tags');
    }
}
