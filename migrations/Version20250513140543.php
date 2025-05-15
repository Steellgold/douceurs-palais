<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250513140543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD required_points INT DEFAULT NULL, DROP is_vegan, DROP is_vegetarian, DROP is_gluten_free, DROP is_lactose_free
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD loyalty_points INT DEFAULT 0 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD is_vegan TINYINT(1) NOT NULL, ADD is_vegetarian TINYINT(1) NOT NULL, ADD is_gluten_free TINYINT(1) NOT NULL, ADD is_lactose_free TINYINT(1) NOT NULL, DROP required_points
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP loyalty_points
        SQL);
    }
}
