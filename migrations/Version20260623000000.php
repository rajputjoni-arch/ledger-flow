<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accounts and transfers tables with initial sample data.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE accounts (id VARCHAR(36) NOT NULL, owner VARCHAR(128) NOT NULL, currency VARCHAR(3) NOT NULL, balance_cents BIGINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB');
        $this->addSql('CREATE TABLE transfers (id VARCHAR(36) NOT NULL, from_account_id VARCHAR(36) NOT NULL, to_account_id VARCHAR(36) NOT NULL, amount_cents BIGINT NOT NULL, currency VARCHAR(3) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_TRANSFERS_FROM_ACCOUNT (from_account_id), INDEX IDX_TRANSFERS_TO_ACCOUNT (to_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB');
        $this->addSql('ALTER TABLE transfers ADD CONSTRAINT FK_TRANSFERS_FROM_ACCOUNT FOREIGN KEY (from_account_id) REFERENCES accounts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfers ADD CONSTRAINT FK_TRANSFERS_TO_ACCOUNT FOREIGN KEY (to_account_id) REFERENCES accounts (id) ON DELETE CASCADE');
        $this->addSql("INSERT INTO accounts (id, owner, currency, balance_cents, created_at, updated_at) VALUES ('acct-1', 'Acme Corp', 'USD', 1200000, NOW(), NOW()), ('acct-2', 'Nimbus Holdings', 'USD', 850000, NOW(), NOW()), ('acct-3', 'Zenith Trust', 'USD', 430000, NOW(), NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE transfers');
        $this->addSql('DROP TABLE accounts');
    }
}
