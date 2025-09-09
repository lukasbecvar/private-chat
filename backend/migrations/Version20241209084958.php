<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20241209084958
 *
 * The database migration for create log entity database table
 *
 * @package DoctrineMigrations
 */
final class Version20241209084958 extends AbstractMigration
{
    /**
     * Get migration description
     *
     * @return string The migration description
     */
    public function getDescription(): string
    {
        return 'Create log entity database table';
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
        $this->addSql('CREATE TABLE logs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, time DATETIME NOT NULL, user_agent VARCHAR(255) NOT NULL, request_uri VARCHAR(255) NOT NULL, request_method VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, level INT NOT NULL, user_id INT NOT NULL, status VARCHAR(255) NOT NULL, INDEX logs_name_idx (name), INDEX logs_time_idx (time), INDEX logs_status_idx (status), INDEX logs_user_id_idx (user_id), INDEX logs_ip_address_idx (ip_address), INDEX logs_request_method_idx (request_method), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
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
        $this->addSql('DROP TABLE logs');
    }
}
