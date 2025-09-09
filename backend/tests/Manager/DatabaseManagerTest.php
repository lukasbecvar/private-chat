<?php

namespace App\Tests\Manager;

use Exception;
use Doctrine\DBAL\Connection;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseManagerTest
 *
 * Test cases for database manager
 *
 * @package App\Tests\Manager
 */
class DatabaseManagerTest extends TestCase
{
    private DatabaseManager $databaseManager;
    private Connection & MockObject $connectionMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->connectionMock = $this->createMock(Connection::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // initialize the database manager instance
        $this->databaseManager = new DatabaseManager(
            $this->connectionMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test truncate table
     *
     * @return void
     */
    public function testTableTruncate(): void
    {
        // expect executeStatement call
        $this->connectionMock->expects($this->once())->method('executeStatement')->with(
            $this->stringContains('TRUNCATE TABLE test_db.test_table')
        );

        // call tested method
        $this->databaseManager->tableTruncate('test_db', 'test_table');
    }

    /**
     * Test truncate table throws exception
     *
     * @return void
     */
    public function testTableTruncateThrowsException(): void
    {
        // expect executeStatement call
        $this->connectionMock->expects($this->once())->method('executeStatement')->willThrowException(new Exception('Database error'));

        // expect handleError call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->stringContains('Error truncating table'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR),
            $this->equalTo('Database error')
        );

        // call tested method
        $this->databaseManager->tableTruncate('test_db', 'test_table');
    }
}
