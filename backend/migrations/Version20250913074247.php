<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20250913074247
 * 
 * The database schema migration
 * 
 * @package DoctrineMigrations
 */
final class Version20250913074247 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Base database migration';
    }

    /**
     * Execute the migration
     *
     * @param Schema $schema The representation of a database schema
     *
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE logs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, time DATETIME NOT NULL, user_agent VARCHAR(255) NOT NULL, request_uri VARCHAR(255) NOT NULL, request_method VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, level INT NOT NULL, user_id INT NOT NULL, status VARCHAR(255) NOT NULL, INDEX logs_name_idx (name), INDEX logs_time_idx (time), INDEX logs_status_idx (status), INDEX logs_user_id_idx (user_id), INDEX logs_ip_address_idx (ip_address), INDEX logs_request_method_idx (request_method), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, register_time DATETIME NOT NULL, last_login_time DATETIME NOT NULL, ip_address VARCHAR(255) NOT NULL, user_agent VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, profile_pic LONGTEXT NOT NULL, INDEX users_username_idx (username), INDEX users_status_idx (status), INDEX users_ip_address_idx (ip_address), UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    /**
     * Undo the migration
     *
     * @param Schema $schema The representation of a database schema
     *
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE logs');
        $this->addSql('DROP TABLE users');
    }
}
