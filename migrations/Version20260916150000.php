<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Une notification peut naître sans application : un brouillon que l'on
 * remplit d'abord et que l'on habille ensuite, avec des fragments de texte
 * à placer dans les champs de l'app le moment venu.
 *
 * Le retour arrière donne aux brouillons l'application historique par
 * défaut, dépubliés, et abandonne leurs fragments.
 */
final class Version20260916150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend l\'application facultative et ajoute les fragments de texte des notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media ALTER app_kind DROP NOT NULL');
        $this->addSql("ALTER TABLE media ADD fragments JSON DEFAULT '[]' NOT NULL");
        $this->addSql('ALTER TABLE media ALTER fragments DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE media SET app_kind = 'uber_eats', published = false WHERE app_kind IS NULL");
        $this->addSql('ALTER TABLE media ALTER app_kind SET NOT NULL');
        $this->addSql('ALTER TABLE media DROP fragments');
    }
}
