<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250424144223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP FOREIGN KEY FK_F52993985570DBC4
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F52993985570DBC4 ON `order`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP bakery_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD bakery_id VARCHAR(36) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD CONSTRAINT FK_F52993985570DBC4 FOREIGN KEY (bakery_id) REFERENCES bakery (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F52993985570DBC4 ON `order` (bakery_id)
        SQL);
    }
}
