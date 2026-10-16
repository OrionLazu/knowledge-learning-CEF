<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema of Knowledge Learning: users, catalogue (theme, course, lesson),
 * orders (purchase, purchase_item), access rights and progress (validations, certifications).
 */
final class Version20261016225254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema of the Knowledge Learning platform';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE certification (id INT AUTO_INCREMENT NOT NULL, obtained_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, theme_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, UNIQUE INDEX uniq_certification_user_theme (user_id, theme_id), INDEX IDX_6C3C6D75A76ED395 (user_id), INDEX IDX_6C3C6D7559027487 (theme_id), INDEX IDX_6C3C6D75DE12AB56 (created_by), INDEX IDX_6C3C6D7516FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, description VARCHAR(255) DEFAULT NULL, price INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, theme_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, INDEX IDX_169E6FB959027487 (theme_id), INDEX IDX_169E6FB9DE12AB56 (created_by), INDEX IDX_169E6FB916FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_validation (id INT AUTO_INCREMENT NOT NULL, validated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, course_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, UNIQUE INDEX uniq_course_validation_user_course (user_id, course_id), INDEX IDX_C94E15EDA76ED395 (user_id), INDEX IDX_C94E15ED591CC992 (course_id), INDEX IDX_C94E15EDDE12AB56 (created_by), INDEX IDX_C94E15ED16FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lesson (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, position INT NOT NULL, content LONGTEXT NOT NULL, video_url VARCHAR(255) DEFAULT NULL, price INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, course_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, INDEX IDX_F87474F3591CC992 (course_id), INDEX IDX_F87474F3DE12AB56 (created_by), INDEX IDX_F87474F316FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lesson_access (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, lesson_id INT NOT NULL, purchase_id INT DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, UNIQUE INDEX uniq_lesson_access_user_lesson (user_id, lesson_id), INDEX IDX_75FFDB03A76ED395 (user_id), INDEX IDX_75FFDB03CDF80196 (lesson_id), INDEX IDX_75FFDB03558FBEB9 (purchase_id), INDEX IDX_75FFDB03DE12AB56 (created_by), INDEX IDX_75FFDB0316FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lesson_validation (id INT AUTO_INCREMENT NOT NULL, validated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, lesson_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, UNIQUE INDEX uniq_lesson_validation_user_lesson (user_id, lesson_id), INDEX IDX_1BA512BFA76ED395 (user_id), INDEX IDX_1BA512BFCDF80196 (lesson_id), INDEX IDX_1BA512BFDE12AB56 (created_by), INDEX IDX_1BA512BF16FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE purchase (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, total INT NOT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, customer_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, UNIQUE INDEX UNIQ_6117D13B1A314A57 (stripe_session_id), INDEX idx_purchase_status (status), INDEX IDX_6117D13B9395C3F3 (customer_id), INDEX IDX_6117D13BDE12AB56 (created_by), INDEX IDX_6117D13B16FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE purchase_item (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, unit_price INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, purchase_id INT NOT NULL, course_id INT DEFAULT NULL, lesson_id INT DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, INDEX IDX_6FA8ED7D558FBEB9 (purchase_id), INDEX IDX_6FA8ED7D591CC992 (course_id), INDEX IDX_6FA8ED7DCDF80196 (lesson_id), INDEX IDX_6FA8ED7DDE12AB56 (created_by), INDEX IDX_6FA8ED7D16FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, UNIQUE INDEX UNIQ_9775E7085E237E06 (name), INDEX IDX_9775E708DE12AB56 (created_by), INDEX IDX_9775E70816FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649DE12AB56 (created_by), INDEX IDX_8D93D64916FE72E1 (updated_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D7559027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D7516FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB959027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB9DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB916FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE course_validation ADD CONSTRAINT FK_C94E15EDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_validation ADD CONSTRAINT FK_C94E15ED591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_validation ADD CONSTRAINT FK_C94E15EDDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE course_validation ADD CONSTRAINT FK_C94E15ED16FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lesson ADD CONSTRAINT FK_F87474F3591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson ADD CONSTRAINT FK_F87474F3DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lesson ADD CONSTRAINT FK_F87474F316FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lesson_access ADD CONSTRAINT FK_75FFDB03A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson_access ADD CONSTRAINT FK_75FFDB03CDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson_access ADD CONSTRAINT FK_75FFDB03558FBEB9 FOREIGN KEY (purchase_id) REFERENCES purchase (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lesson_access ADD CONSTRAINT FK_75FFDB03DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lesson_access ADD CONSTRAINT FK_75FFDB0316FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lesson_validation ADD CONSTRAINT FK_1BA512BFA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson_validation ADD CONSTRAINT FK_1BA512BFCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson_validation ADD CONSTRAINT FK_1BA512BFDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lesson_validation ADD CONSTRAINT FK_1BA512BF16FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE purchase ADD CONSTRAINT FK_6117D13B9395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE purchase ADD CONSTRAINT FK_6117D13BDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE purchase ADD CONSTRAINT FK_6117D13B16FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE purchase_item ADD CONSTRAINT FK_6FA8ED7D558FBEB9 FOREIGN KEY (purchase_id) REFERENCES purchase (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE purchase_item ADD CONSTRAINT FK_6FA8ED7D591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE purchase_item ADD CONSTRAINT FK_6FA8ED7DCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE purchase_item ADD CONSTRAINT FK_6FA8ED7DDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE purchase_item ADD CONSTRAINT FK_6FA8ED7D16FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E708DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E70816FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64916FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75A76ED395');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D7559027487');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75DE12AB56');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D7516FE72E1');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB959027487');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB9DE12AB56');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB916FE72E1');
        $this->addSql('ALTER TABLE course_validation DROP FOREIGN KEY FK_C94E15EDA76ED395');
        $this->addSql('ALTER TABLE course_validation DROP FOREIGN KEY FK_C94E15ED591CC992');
        $this->addSql('ALTER TABLE course_validation DROP FOREIGN KEY FK_C94E15EDDE12AB56');
        $this->addSql('ALTER TABLE course_validation DROP FOREIGN KEY FK_C94E15ED16FE72E1');
        $this->addSql('ALTER TABLE lesson DROP FOREIGN KEY FK_F87474F3591CC992');
        $this->addSql('ALTER TABLE lesson DROP FOREIGN KEY FK_F87474F3DE12AB56');
        $this->addSql('ALTER TABLE lesson DROP FOREIGN KEY FK_F87474F316FE72E1');
        $this->addSql('ALTER TABLE lesson_access DROP FOREIGN KEY FK_75FFDB03A76ED395');
        $this->addSql('ALTER TABLE lesson_access DROP FOREIGN KEY FK_75FFDB03CDF80196');
        $this->addSql('ALTER TABLE lesson_access DROP FOREIGN KEY FK_75FFDB03558FBEB9');
        $this->addSql('ALTER TABLE lesson_access DROP FOREIGN KEY FK_75FFDB03DE12AB56');
        $this->addSql('ALTER TABLE lesson_access DROP FOREIGN KEY FK_75FFDB0316FE72E1');
        $this->addSql('ALTER TABLE lesson_validation DROP FOREIGN KEY FK_1BA512BFA76ED395');
        $this->addSql('ALTER TABLE lesson_validation DROP FOREIGN KEY FK_1BA512BFCDF80196');
        $this->addSql('ALTER TABLE lesson_validation DROP FOREIGN KEY FK_1BA512BFDE12AB56');
        $this->addSql('ALTER TABLE lesson_validation DROP FOREIGN KEY FK_1BA512BF16FE72E1');
        $this->addSql('ALTER TABLE purchase DROP FOREIGN KEY FK_6117D13B9395C3F3');
        $this->addSql('ALTER TABLE purchase DROP FOREIGN KEY FK_6117D13BDE12AB56');
        $this->addSql('ALTER TABLE purchase DROP FOREIGN KEY FK_6117D13B16FE72E1');
        $this->addSql('ALTER TABLE purchase_item DROP FOREIGN KEY FK_6FA8ED7D558FBEB9');
        $this->addSql('ALTER TABLE purchase_item DROP FOREIGN KEY FK_6FA8ED7D591CC992');
        $this->addSql('ALTER TABLE purchase_item DROP FOREIGN KEY FK_6FA8ED7DCDF80196');
        $this->addSql('ALTER TABLE purchase_item DROP FOREIGN KEY FK_6FA8ED7DDE12AB56');
        $this->addSql('ALTER TABLE purchase_item DROP FOREIGN KEY FK_6FA8ED7D16FE72E1');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E708DE12AB56');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E70816FE72E1');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649DE12AB56');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64916FE72E1');
        $this->addSql('DROP TABLE certification');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE course_validation');
        $this->addSql('DROP TABLE lesson');
        $this->addSql('DROP TABLE lesson_access');
        $this->addSql('DROP TABLE lesson_validation');
        $this->addSql('DROP TABLE purchase');
        $this->addSql('DROP TABLE purchase_item');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE `user`');
    }
}
