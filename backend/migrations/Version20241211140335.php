<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20241211140335
 *
 * The database migration for create user entity database table
 *
 * @package DoctrineMigrations
 */
final class Version20241211140335 extends AbstractMigration
{
    /**
     * Get migration description
     *
     * @return string The migration description
     */
    public function getDescription(): string
    {
        return 'Create user entity database table';
    }

    /**
     * Excute migration
     *
     * @param Schema $schema The representation of a database schema
     *
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, register_time DATETIME NOT NULL, last_login_time DATETIME NOT NULL, ip_address VARCHAR(255) NOT NULL, user_agent VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, INDEX users_email_idx (email), INDEX users_status_idx (status), INDEX users_last_name_idx (last_name), INDEX users_first_name_idx (first_name), INDEX users_ip_address_idx (ip_address), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    /**
     * Excute undo migration
     *
     * @param Schema $schema The representation of a database schema
     *
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
