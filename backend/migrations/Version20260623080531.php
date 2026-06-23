<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623080531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add security_events table for login audit trail (F004q)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE security_events (id UUID NOT NULL, event_type VARCHAR(255) NOT NULL, user_id UUID DEFAULT NULL, email_attempted VARCHAR(180) DEFAULT NULL, reason VARCHAR(32) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_security_events_user_id_created_at ON security_events (user_id, created_at)');
        $this->addSql('CREATE INDEX idx_security_events_ip_created_at ON security_events (ip, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE security_events');
    }
}
