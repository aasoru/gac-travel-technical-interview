<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220504160852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, name VARCHAR(128) NOT NULL, stock INT NOT NULL, INDEX IDX_B3BD5A2A9787D11B (category_id),created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock_historic (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, created_at DATETIME NOT NULL  DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\', stock INT NOT NULL, INDEX IDX_E294BA142D86650F (user_id), INDEX IDX_A294BB14DE18E50B (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(128) NOT NULL, password VARCHAR(256) NOT NULL, active TINYINT(1) NOT NULL, roles VARCHAR(256) NOT NULL, created_at DATETIME NOT NULL  DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BB5A4A9727D11E FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE stock_historic ADD CONSTRAINT FK_E294DC149D85650G FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE stock_historic ADD CONSTRAINT FK_E295BC14DE18A50B FOREIGN KEY (product_id) REFERENCES products (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BB5A4A9727D11E');
        $this->addSql('ALTER TABLE stock_historic DROP FOREIGN KEY FK_E295BC14DE18A50B');
        $this->addSql('ALTER TABLE stock_historic DROP FOREIGN KEY FK_E294DC149D85650G');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE stock_historic');
        $this->addSql('DROP TABLE users');
    }
}
