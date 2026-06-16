<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616192850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync messenger_messages indexes with Symfony 7 combined index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_75ea56e0fb7336f0');
        $this->addSql('DROP INDEX IF EXISTS idx_75ea56e0e3bd61ce');
        $this->addSql('DROP INDEX IF EXISTS idx_75ea56e016ba31db');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750');
        $this->addSql('CREATE INDEX idx_75ea56e0fb7336f0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX idx_75ea56e0e3bd61ce ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_75ea56e016ba31db ON messenger_messages (delivered_at)');
    }
}
