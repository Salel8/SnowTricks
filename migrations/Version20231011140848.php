<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231011140848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE picture_post (id INT AUTO_INCREMENT NOT NULL, id_post_id INT DEFAULT NULL, picture_filename VARCHAR(550) NOT NULL, INDEX IDX_E56AE9189514AA5C (id_post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE video_post (id INT AUTO_INCREMENT NOT NULL, id_post_id INT DEFAULT NULL, video_filename VARCHAR(550) NOT NULL, INDEX IDX_5D1D31519514AA5C (id_post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE picture_post ADD CONSTRAINT FK_E56AE9189514AA5C FOREIGN KEY (id_post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE video_post ADD CONSTRAINT FK_5D1D31519514AA5C FOREIGN KEY (id_post_id) REFERENCES post (id)');
        $this->addSql('DROP TABLE user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(250) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, mail VARCHAR(250) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, validity_account VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE picture_post DROP FOREIGN KEY FK_E56AE9189514AA5C');
        $this->addSql('ALTER TABLE video_post DROP FOREIGN KEY FK_5D1D31519514AA5C');
        $this->addSql('DROP TABLE picture_post');
        $this->addSql('DROP TABLE video_post');
    }
}
