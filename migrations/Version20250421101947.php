<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250421101947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE bakery (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, story LONGTEXT DEFAULT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, rating DOUBLE PRECISION DEFAULT NULL, images JSON DEFAULT NULL, opening_hours JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_C647FA2A989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_favorite_bakeries (user_id VARCHAR(36) NOT NULL, bakery_id VARCHAR(36) NOT NULL, INDEX IDX_EC32BC5CA76ED395 (user_id), INDEX IDX_EC32BC5C5570DBC4 (bakery_id), PRIMARY KEY(user_id, bakery_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_favorite_bakeries ADD CONSTRAINT FK_EC32BC5CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_favorite_bakeries ADD CONSTRAINT FK_EC32BC5C5570DBC4 FOREIGN KEY (bakery_id) REFERENCES bakery (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD bakery_id VARCHAR(36) DEFAULT NULL, ADD popularity INT DEFAULT NULL, ADD category VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04AD5570DBC4 FOREIGN KEY (bakery_id) REFERENCES bakery (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04AD5570DBC4 ON product (bakery_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD5570DBC4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_favorite_bakeries DROP FOREIGN KEY FK_EC32BC5CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_favorite_bakeries DROP FOREIGN KEY FK_EC32BC5C5570DBC4
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE bakery
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_favorite_bakeries
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D34A04AD5570DBC4 ON product
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP bakery_id, DROP popularity, DROP category
        SQL);
    }
}
