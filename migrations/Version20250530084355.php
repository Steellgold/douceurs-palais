<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530084355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ingredient (id VARCHAR(36) NOT NULL, bakery_id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, allergens JSON DEFAULT NULL, is_vegan TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6BAF78705570DBC4 (bakery_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product_ingredient (product_id VARCHAR(36) NOT NULL, ingredient_id VARCHAR(36) NOT NULL, INDEX IDX_F8C8275B4584665A (product_id), INDEX IDX_F8C8275B933FE08C (ingredient_id), PRIMARY KEY(product_id, ingredient_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ingredient ADD CONSTRAINT FK_6BAF78705570DBC4 FOREIGN KEY (bakery_id) REFERENCES bakery (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_ingredient ADD CONSTRAINT FK_F8C8275B4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_ingredient ADD CONSTRAINT FK_F8C8275B933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD is_vegan TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ingredient DROP FOREIGN KEY FK_6BAF78705570DBC4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_ingredient DROP FOREIGN KEY FK_F8C8275B4584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_ingredient DROP FOREIGN KEY FK_F8C8275B933FE08C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ingredient
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product_ingredient
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP is_vegan
        SQL);
    }
}
