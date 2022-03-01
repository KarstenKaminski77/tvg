<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220224125413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE addresses (id INT AUTO_INCREMENT NOT NULL, clinic_id INT DEFAULT NULL, address VARCHAR(255) NOT NULL, is_default TINYINT(1) DEFAULT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, clinic_name VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, postal_code VARCHAR(255) NOT NULL, suite VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) DEFAULT NULL, INDEX IDX_6FCA7516CC22AD4 (clinic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE availability_tracker (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, clinic_id INT DEFAULT NULL, communication_id INT DEFAULT NULL, is_sent TINYINT(1) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_2377C1044584665A (product_id), INDEX IDX_2377C104CC22AD4 (clinic_id), INDEX IDX_2377C1041C2D1E0C (communication_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE baskets (id INT AUTO_INCREMENT NOT NULL, clinic_id INT DEFAULT NULL, distributor_id INT DEFAULT NULL, status_id INT DEFAULT NULL, total DOUBLE PRECISION DEFAULT NULL, name VARCHAR(255) NOT NULL, saved_by VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_DCFB21EFCC22AD4 (clinic_id), INDEX IDX_DCFB21EF2D863A58 (distributor_id), INDEX IDX_DCFB21EF6BF700BD (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE blog (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, thumbnail VARCHAR(255) DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, body TEXT DEFAULT NULL, meta_title VARCHAR(60) DEFAULT NULL, meta_description TINYTEXT DEFAULT NULL, keyword_1 VARCHAR(50) DEFAULT NULL, keyword_2 VARCHAR(50) DEFAULT NULL, keyword_3 VARCHAR(50) DEFAULT NULL, author VARCHAR(255) DEFAULT NULL, infographic_1 TINYTEXT DEFAULT NULL, infographic_2 TINYTEXT DEFAULT NULL, is_promotion TINYINT(1) DEFAULT NULL, is_home_page TINYINT(1) DEFAULT NULL, is_featured TINYINT(1) DEFAULT NULL, last_updated DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, display_date DATETIME DEFAULT NULL, FULLTEXT INDEX title_idx (title), FULLTEXT INDEX keyword_1_idx (keyword_1), FULLTEXT INDEX keyword_2_idx (keyword_2), FULLTEXT INDEX keyword_3_idx (keyword_3), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_blog (blog_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_4B8E2B04DAE07E97 (blog_id), INDEX IDX_4B8E2B0412469DE2 (category_id), PRIMARY KEY(blog_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, category_name VARCHAR(255) DEFAULT NULL, count INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE clinic_communication_methods (id INT AUTO_INCREMENT NOT NULL, clinic_id INT DEFAULT NULL, communication_method_id INT DEFAULT NULL, send_to VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_F6F3B96ECC22AD4 (clinic_id), INDEX IDX_F6F3B96E1B6E6104 (communication_method_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE clinic_users (id INT AUTO_INCREMENT NOT NULL, clinic_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, position VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, is_primary TINYINT(1) NOT NULL, telephone VARCHAR(255) NOT NULL, INDEX IDX_9D070CDACC22AD4 (clinic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE clinics (id INT AUTO_INCREMENT NOT NULL, clinic_name VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE communication_methods (id INT AUTO_INCREMENT NOT NULL, method VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE distributor_clinic_prices (id INT AUTO_INCREMENT NOT NULL, distributor_id INT DEFAULT NULL, clinic_id INT DEFAULT NULL, product_id INT DEFAULT NULL, unit_price DOUBLE PRECISION NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_57C3D89B2D863A58 (distributor_id), INDEX IDX_57C3D89BCC22AD4 (clinic_id), INDEX IDX_57C3D89B4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE distributor_products (id INT AUTO_INCREMENT NOT NULL, distributor_id INT DEFAULT NULL, product_id INT DEFAULT NULL, sku VARCHAR(255) NOT NULL, distributor_no VARCHAR(255) NOT NULL, unit_price DOUBLE PRECISION NOT NULL, stock_count INT NOT NULL, expiry_date DATE NOT NULL, tax_exempt TINYINT(1) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_24ECE11F2D863A58 (distributor_id), INDEX IDX_24ECE11F4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE distributor_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_5090E3B7E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE distributor_users (id INT AUTO_INCREMENT NOT NULL, distributor_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, position VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_4E5B79C72D863A58 (distributor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE distributors (id INT AUTO_INCREMENT NOT NULL, distributor_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, position VARCHAR(255) NOT NULL, logo VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, website VARCHAR(255) NOT NULL, about VARCHAR(255) NOT NULL, operating_hours LONGTEXT NOT NULL, refund_policy LONGTEXT NOT NULL, sales_tax_policy LONGTEXT NOT NULL, is_manufaturer TINYINT(1) NOT NULL, theme_id INT DEFAULT NULL, roles JSON NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_log (id INT AUTO_INCREMENT NOT NULL, distributor_id INT DEFAULT NULL, clinic_id INT DEFAULT NULL, orders_id INT DEFAULT NULL, status_id INT DEFAULT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_9EF0AD162D863A58 (distributor_id), INDEX IDX_9EF0AD16CC22AD4 (clinic_id), INDEX IDX_9EF0AD16CFFE9AD6 (orders_id), INDEX IDX_9EF0AD166BF700BD (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lists (id INT AUTO_INCREMENT NOT NULL, clinic_id INT DEFAULT NULL, list_typs VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, item_count INT NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_8269FA5CC22AD4 (clinic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, orders_id INT DEFAULT NULL, product_id INT DEFAULT NULL, distributor_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price DOUBLE PRECISION NOT NULL, total DOUBLE PRECISION NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_62809DB0CFFE9AD6 (orders_id), INDEX IDX_62809DB04584665A (product_id), INDEX IDX_62809DB02D863A58 (distributor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, clinic_id INT DEFAULT NULL, address_id INT DEFAULT NULL, status_id INT DEFAULT NULL, delivery_fee DOUBLE PRECISION DEFAULT NULL, sub_total DOUBLE PRECISION NOT NULL, tax DOUBLE PRECISION NOT NULL, total DOUBLE PRECISION NOT NULL, purchase_order VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_E52FFDEECC22AD4 (clinic_id), INDEX IDX_E52FFDEEF5B7AF75 (address_id), INDEX IDX_E52FFDEE6BF700BD (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_notes (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, clinic_id INT DEFAULT NULL, clinic_user_id INT DEFAULT NULL, note LONGTEXT NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_A047DDD04584665A (product_id), INDEX IDX_A047DDD0CC22AD4 (clinic_id), INDEX IDX_A047DDD0FC0F246D (clinic_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_reviews (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, clinic_user_id INT DEFAULT NULL, parent_id INT NOT NULL, subject VARCHAR(255) NOT NULL, review LONGTEXT NOT NULL, clinic VARCHAR(255) NOT NULL, likes INT NOT NULL, rating INT NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_B8A9F0BF4584665A (product_id), INDEX IDX_B8A9F0BFFC0F246D (clinic_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, sub_category_id INT DEFAULT NULL, is_published TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, active_ingredient VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, dosage VARCHAR(255) NOT NULL, size VARCHAR(255) NOT NULL, form VARCHAR(255) NOT NULL, pack_type VARCHAR(255) NOT NULL, unit VARCHAR(255) NOT NULL, unit_price DOUBLE PRECISION NOT NULL, stock_count INT NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_B3BA5A5A12469DE2 (category_id), INDEX IDX_B3BA5A5AF7BFE87C (sub_category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE products_species (products_id INT NOT NULL, species_id INT NOT NULL, INDEX IDX_7E320D4F6C8A81A9 (products_id), INDEX IDX_7E320D4FB2A1D860 (species_id), PRIMARY KEY(products_id, species_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE species (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE status (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sub_categories (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, sub_category VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_1638D5A512469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA7516CC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE availability_tracker ADD CONSTRAINT FK_2377C1044584665A FOREIGN KEY (product_id) REFERENCES products (id)');
        $this->addSql('ALTER TABLE availability_tracker ADD CONSTRAINT FK_2377C104CC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE availability_tracker ADD CONSTRAINT FK_2377C1041C2D1E0C FOREIGN KEY (communication_id) REFERENCES communication_methods (id)');
        $this->addSql('ALTER TABLE baskets ADD CONSTRAINT FK_DCFB21EFCC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE baskets ADD CONSTRAINT FK_DCFB21EF2D863A58 FOREIGN KEY (distributor_id) REFERENCES distributors (id)');
        $this->addSql('ALTER TABLE baskets ADD CONSTRAINT FK_DCFB21EF6BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('ALTER TABLE category_blog ADD CONSTRAINT FK_4B8E2B04DAE07E97 FOREIGN KEY (blog_id) REFERENCES blog (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_blog ADD CONSTRAINT FK_4B8E2B0412469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clinic_communication_methods ADD CONSTRAINT FK_F6F3B96ECC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE clinic_communication_methods ADD CONSTRAINT FK_F6F3B96E1B6E6104 FOREIGN KEY (communication_method_id) REFERENCES communication_methods (id)');
        $this->addSql('ALTER TABLE clinic_users ADD CONSTRAINT FK_9D070CDACC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE distributor_clinic_prices ADD CONSTRAINT FK_57C3D89B2D863A58 FOREIGN KEY (distributor_id) REFERENCES distributors (id)');
        $this->addSql('ALTER TABLE distributor_clinic_prices ADD CONSTRAINT FK_57C3D89BCC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE distributor_clinic_prices ADD CONSTRAINT FK_57C3D89B4584665A FOREIGN KEY (product_id) REFERENCES distributor_products (id)');
        $this->addSql('ALTER TABLE distributor_products ADD CONSTRAINT FK_24ECE11F2D863A58 FOREIGN KEY (distributor_id) REFERENCES distributors (id)');
        $this->addSql('ALTER TABLE distributor_products ADD CONSTRAINT FK_24ECE11F4584665A FOREIGN KEY (product_id) REFERENCES products (id)');
        $this->addSql('ALTER TABLE distributor_users ADD CONSTRAINT FK_4E5B79C72D863A58 FOREIGN KEY (distributor_id) REFERENCES distributors (id)');
        $this->addSql('ALTER TABLE event_log ADD CONSTRAINT FK_9EF0AD162D863A58 FOREIGN KEY (distributor_id) REFERENCES distributors (id)');
        $this->addSql('ALTER TABLE event_log ADD CONSTRAINT FK_9EF0AD16CC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE event_log ADD CONSTRAINT FK_9EF0AD16CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE event_log ADD CONSTRAINT FK_9EF0AD166BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('ALTER TABLE lists ADD CONSTRAINT FK_8269FA5CC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB0CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES products (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB02D863A58 FOREIGN KEY (distributor_id) REFERENCES distributors (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEECC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEF5B7AF75 FOREIGN KEY (address_id) REFERENCES addresses (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE6BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('ALTER TABLE product_notes ADD CONSTRAINT FK_A047DDD04584665A FOREIGN KEY (product_id) REFERENCES products (id)');
        $this->addSql('ALTER TABLE product_notes ADD CONSTRAINT FK_A047DDD0CC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinics (id)');
        $this->addSql('ALTER TABLE product_notes ADD CONSTRAINT FK_A047DDD0FC0F246D FOREIGN KEY (clinic_user_id) REFERENCES clinic_users (id)');
        $this->addSql('ALTER TABLE product_reviews ADD CONSTRAINT FK_B8A9F0BF4584665A FOREIGN KEY (product_id) REFERENCES products (id)');
        $this->addSql('ALTER TABLE product_reviews ADD CONSTRAINT FK_B8A9F0BFFC0F246D FOREIGN KEY (clinic_user_id) REFERENCES clinic_users (id)');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5AF7BFE87C FOREIGN KEY (sub_category_id) REFERENCES sub_categories (id)');
        $this->addSql('ALTER TABLE products_species ADD CONSTRAINT FK_7E320D4F6C8A81A9 FOREIGN KEY (products_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE products_species ADD CONSTRAINT FK_7E320D4FB2A1D860 FOREIGN KEY (species_id) REFERENCES species (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sub_categories ADD CONSTRAINT FK_1638D5A512469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEF5B7AF75');
        $this->addSql('ALTER TABLE category_blog DROP FOREIGN KEY FK_4B8E2B04DAE07E97');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A12469DE2');
        $this->addSql('ALTER TABLE sub_categories DROP FOREIGN KEY FK_1638D5A512469DE2');
        $this->addSql('ALTER TABLE category_blog DROP FOREIGN KEY FK_4B8E2B0412469DE2');
        $this->addSql('ALTER TABLE product_notes DROP FOREIGN KEY FK_A047DDD0FC0F246D');
        $this->addSql('ALTER TABLE product_reviews DROP FOREIGN KEY FK_B8A9F0BFFC0F246D');
        $this->addSql('ALTER TABLE addresses DROP FOREIGN KEY FK_6FCA7516CC22AD4');
        $this->addSql('ALTER TABLE availability_tracker DROP FOREIGN KEY FK_2377C104CC22AD4');
        $this->addSql('ALTER TABLE baskets DROP FOREIGN KEY FK_DCFB21EFCC22AD4');
        $this->addSql('ALTER TABLE clinic_communication_methods DROP FOREIGN KEY FK_F6F3B96ECC22AD4');
        $this->addSql('ALTER TABLE clinic_users DROP FOREIGN KEY FK_9D070CDACC22AD4');
        $this->addSql('ALTER TABLE distributor_clinic_prices DROP FOREIGN KEY FK_57C3D89BCC22AD4');
        $this->addSql('ALTER TABLE event_log DROP FOREIGN KEY FK_9EF0AD16CC22AD4');
        $this->addSql('ALTER TABLE lists DROP FOREIGN KEY FK_8269FA5CC22AD4');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEECC22AD4');
        $this->addSql('ALTER TABLE product_notes DROP FOREIGN KEY FK_A047DDD0CC22AD4');
        $this->addSql('ALTER TABLE availability_tracker DROP FOREIGN KEY FK_2377C1041C2D1E0C');
        $this->addSql('ALTER TABLE clinic_communication_methods DROP FOREIGN KEY FK_F6F3B96E1B6E6104');
        $this->addSql('ALTER TABLE distributor_clinic_prices DROP FOREIGN KEY FK_57C3D89B4584665A');
        $this->addSql('ALTER TABLE baskets DROP FOREIGN KEY FK_DCFB21EF2D863A58');
        $this->addSql('ALTER TABLE distributor_clinic_prices DROP FOREIGN KEY FK_57C3D89B2D863A58');
        $this->addSql('ALTER TABLE distributor_products DROP FOREIGN KEY FK_24ECE11F2D863A58');
        $this->addSql('ALTER TABLE distributor_users DROP FOREIGN KEY FK_4E5B79C72D863A58');
        $this->addSql('ALTER TABLE event_log DROP FOREIGN KEY FK_9EF0AD162D863A58');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB02D863A58');
        $this->addSql('ALTER TABLE event_log DROP FOREIGN KEY FK_9EF0AD16CFFE9AD6');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB0CFFE9AD6');
        $this->addSql('ALTER TABLE availability_tracker DROP FOREIGN KEY FK_2377C1044584665A');
        $this->addSql('ALTER TABLE distributor_products DROP FOREIGN KEY FK_24ECE11F4584665A');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('ALTER TABLE product_notes DROP FOREIGN KEY FK_A047DDD04584665A');
        $this->addSql('ALTER TABLE product_reviews DROP FOREIGN KEY FK_B8A9F0BF4584665A');
        $this->addSql('ALTER TABLE products_species DROP FOREIGN KEY FK_7E320D4F6C8A81A9');
        $this->addSql('ALTER TABLE products_species DROP FOREIGN KEY FK_7E320D4FB2A1D860');
        $this->addSql('ALTER TABLE baskets DROP FOREIGN KEY FK_DCFB21EF6BF700BD');
        $this->addSql('ALTER TABLE event_log DROP FOREIGN KEY FK_9EF0AD166BF700BD');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE6BF700BD');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5AF7BFE87C');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE addresses');
        $this->addSql('DROP TABLE availability_tracker');
        $this->addSql('DROP TABLE baskets');
        $this->addSql('DROP TABLE blog');
        $this->addSql('DROP TABLE category_blog');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE clinic_communication_methods');
        $this->addSql('DROP TABLE clinic_users');
        $this->addSql('DROP TABLE clinics');
        $this->addSql('DROP TABLE communication_methods');
        $this->addSql('DROP TABLE distributor_clinic_prices');
        $this->addSql('DROP TABLE distributor_products');
        $this->addSql('DROP TABLE distributor_user');
        $this->addSql('DROP TABLE distributor_users');
        $this->addSql('DROP TABLE distributors');
        $this->addSql('DROP TABLE email');
        $this->addSql('DROP TABLE event_log');
        $this->addSql('DROP TABLE lists');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE product_notes');
        $this->addSql('DROP TABLE product_reviews');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE products_species');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE species');
        $this->addSql('DROP TABLE status');
        $this->addSql('DROP TABLE sub_categories');
        $this->addSql('DROP TABLE user');
    }
}
