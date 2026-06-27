<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626203350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create password_reset_requests table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE password_reset_requests (id UUID NOT NULL, user_id UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, consumed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_password_reset_token_hash ON password_reset_requests (token_hash)');
        $this->addSql('CREATE INDEX idx_password_reset_user_id ON password_reset_requests (user_id)');
        $this->addSql('ALTER TABLE password_reset_requests ADD CONSTRAINT FK_9075A748A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_requests DROP CONSTRAINT FK_9075A748A76ED395');
        $this->addSql('DROP TABLE password_reset_requests');
    }
}
