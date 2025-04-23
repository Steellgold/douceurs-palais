<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422185457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE bakery ADD baker_id VARCHAR(36) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bakery ADD CONSTRAINT FK_C647FA2AB1FA4C6F FOREIGN KEY (baker_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C647FA2AB1FA4C6F ON bakery (baker_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE bakery DROP FOREIGN KEY FK_C647FA2AB1FA4C6F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_C647FA2AB1FA4C6F ON bakery
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bakery DROP baker_id
        SQL);
    }
}
