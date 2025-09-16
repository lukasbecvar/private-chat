<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20250916095154
 * 
 * The database schema migration
 * 
 * @package DoctrineMigrations
 */
final class Version20250916095154 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Add private_messages table';
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
        $this->addSql('CREATE TABLE private_messages (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, chat_id VARCHAR(64) NOT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, INDEX chat_id_idx (chat_id), INDEX sender_idx (sender_id), INDEX timestamp_idx (timestamp), INDEX receiver_idx (receiver_id), INDEX chat_timestamp_idx (chat_id, timestamp), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE private_messages ADD CONSTRAINT FK_7C94C13BF624B39D FOREIGN KEY (sender_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE private_messages ADD CONSTRAINT FK_7C94C13BCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES users (id)');
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
        $this->addSql('ALTER TABLE private_messages DROP FOREIGN KEY FK_7C94C13BF624B39D');
        $this->addSql('ALTER TABLE private_messages DROP FOREIGN KEY FK_7C94C13BCD53EDB6');
        $this->addSql('DROP TABLE private_messages');
    }
}
