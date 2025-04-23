<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250423184219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE bakery DROP FOREIGN KEY FK_C647FA2AB1FA4C6F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_C647FA2AB1FA4C6F ON bakery
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bakery DROP baker_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD managed_bakery_id VARCHAR(36) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D64959181A75 FOREIGN KEY (managed_bakery_id) REFERENCES bakery (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D64959181A75 ON user (managed_bakery_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE bakery ADD baker_id VARCHAR(36) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bakery ADD CONSTRAINT FK_C647FA2AB1FA4C6F FOREIGN KEY (baker_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C647FA2AB1FA4C6F ON bakery (baker_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64959181A75
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D64959181A75 ON `user`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP managed_bakery_id
        SQL);
    }
}
