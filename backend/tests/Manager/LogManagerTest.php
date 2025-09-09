<?php

namespace App\Tests\Manager;

use Exception;
use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use App\Repository\LogRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LogManagerTest
 *
 * Test cases for log manager
 *
 * @package App\Test\Manager
 */
class LogManagerTest extends TestCase
{
    private LogManager $logManager;
    private AppUtil & MockObject $appUtilMock;
    private Security & MockObject $securityMock;
    private SecurityUtil & MockObject $securityUtilMock;
    private ErrorManager & MockObject $errorManagerMock;
    private LogRepository & MockObject $logRepositoryMock;
    private DatabaseManager & MockObject $databaseManagerMock;
    private VisitorInfoUtil & MockObject $visitorInfoUtilMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->securityMock = $this->createMock(Security::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->logRepositoryMock = $this->createMock(LogRepository::class);
        $this->databaseManagerMock = $this->createMock(DatabaseManager::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // create log manager instance
        $this->logManager = new LogManager(
            $this->appUtilMock,
            $this->securityMock,
            $this->securityUtilMock,
            $this->errorManagerMock,
            $this->logRepositoryMock,
            $this->databaseManagerMock,
            $this->visitorInfoUtilMock,
            $this->entityManagerMock
        );
    }

    /**
     * Tets save log to database when logging is enabled
     *
     * @return void
     */
    public function testSaveLogWhenLoggingIsDisabled(): void
    {
        // mock database logging to be disabled
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(false);

        // expect that persist and flush will not be called
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->logManager->saveLog('Test', 'Test message');
    }

    /**
     * Tests save log to database when log level is too low
     *
     * @return void
     */
    public function testSaveLogWhenLogLevelIsTooLow(): void
    {
        // mock log manager config
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn('2'); // LEVEL_WARNING (2)

        // expect that persist and flush will not be called
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->logManager->saveLog('Test', 'Test message', LogManager::LEVEL_INFO); // LEVEL_INFO
    }

    /**
     * Tests save log to database with success result
     *
     * @return void
     */
    public function testSaveLogWithSuccess(): void
    {
        // mock log manager config
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn('4'); // LEVEL_INFO

        // mock escape string method
        $this->securityUtilMock->method('escapeString')->willReturnCallback(function ($string) {
            return $string;
        });

        // mock visitor info
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('TestAgent');
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');

        // mock request info
        $this->appUtilMock->method('getRequestUri')->willReturn('/test-uri');
        $this->appUtilMock->method('getRequestMethod')->willReturn('POST');

        // expect that persist and flush will be called once
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->saveLog('Test', 'Test message');
    }

    /**
     * Tests save log to database with exception
     *
     * @return void
     */
    public function testSaveLogThrowsException(): void
    {
        // mock log manager config
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn('4'); // LEVEL_INFO

        // mock escape string method
        $this->securityUtilMock->method('escapeString')->willReturnCallback(function ($string) {
            return $string;
        });

        // mock visitor info
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('TestAgent');
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');

        // mock exception during persist
        $this->entityManagerMock->method('persist')->willThrowException(new Exception('Database error'));

        // expect error handler call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->equalTo('Error save log to database'),
            $this->equalTo(JsonResponse::HTTP_INTERNAL_SERVER_ERROR),
            $this->equalTo('Database error')
        );

        // call tested method
        $this->logManager->saveLog('Test', 'Test message');
    }

    /**
     * Test get logs by status with pagination
     *
     * @return void
     */
    public function testGetLogsByStatus(): void
    {
        // mock pagination limit from env
        $this->appUtilMock->method('getEnvValue')->with('LIMIT_CONTENT_PER_PAGE')->willReturn('10');

        // mock repository to return fake logs
        $fakeLogs = [$this->createMock(Log::class), $this->createMock(Log::class)];
        $this->logRepositoryMock->method('findByStatus')->with('UNREADED', 1, 10)->willReturn($fakeLogs);

        // call tested method
        $logs = $this->logManager->getLogsByStatus('UNREADED', 1);

        // assert logs are returned as expected
        $this->assertCount(2, $logs);
        $this->assertInstanceOf(Log::class, $logs[0]);
    }

    /**
     * Test get logs by user id with pagination
     *
     * @return void
     */
    public function testGetLogsByUserId(): void
    {
        // mock pagination limit from env
        $this->appUtilMock->method('getEnvValue')->with('LIMIT_CONTENT_PER_PAGE')->willReturn('10');

        // mock repository to return fake logs
        $fakeLogs = [$this->createMock(Log::class)];
        $this->logRepositoryMock->method('findByUserId')->with(1, 1, 10)->willReturn($fakeLogs);

        // call tested method
        $logs = $this->logManager->getLogsByUserId(1, 1);

        // assert logs are returned as expected
        $this->assertCount(1, $logs);
        $this->assertInstanceOf(Log::class, $logs[0]);
    }

    /**
     * Test get logs by ip address with pagination
     *
     * @return void
     */
    public function testGetLogsByIpAddress(): void
    {
        // mock pagination limit from env
        $this->appUtilMock->method('getEnvValue')->with('LIMIT_CONTENT_PER_PAGE')->willReturn('10');

        // mock repository to return fake logs
        $fakeLogs = [$this->createMock(Log::class)];
        $this->logRepositoryMock->method('findByIpAddress')->with('127.0.0.1', 1, 10)->willReturn($fakeLogs);

        // call tested method
        $logs = $this->logManager->getLogsByIpAddress('127.0.0.1', 1);

        // assert logs are returned as expected
        $this->assertCount(1, $logs);
        $this->assertInstanceOf(Log::class, $logs[0]);
    }

    /**
     * Test get logs with username
     *
     * @return void
     */
    public function testGetLogsWithUsername(): void
    {
        // mock pagination limit from env
        $this->appUtilMock->method('getEnvValue')->with('LIMIT_CONTENT_PER_PAGE')->willReturn('10');

        // mock repository to return fake logs
        $fakeLogs = [$this->createMock(Log::class)];
        $this->logRepositoryMock->method('findLogsWithUsername')->with(['status' => 'UNREADED'], 1, 10)->willReturn($fakeLogs);

        // call tested method
        $logs = $this->logManager->getLogsWithUsername(['status' => 'UNREADED'], 1);

        // assert logs are returned as expected
        $this->assertCount(1, $logs);
        $this->assertInstanceOf(Log::class, $logs[0]);
    }

    /**
     * Test update log status
     *
     * @return void
     */
    public function testUpdateLogStatus(): void
    {
        // mock log repository to return fake log
        $fakeLog = $this->createMock(Log::class);
        $fakeLog->expects($this->once())->method('setStatus')->with('READED');
        $this->logRepositoryMock->method('find')->with(1)->willReturn($fakeLog);

        // expect entity manager to flush changes
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->updateLogStatus(1, 'READED');
    }

    /**
     * Test set all logs status to readed
     *
     * @return void
     */
    public function testSetAllLogsToReaded(): void
    {
        // mock repository to return fake logs
        $fakeLogs = [$this->createMock(Log::class), $this->createMock(Log::class)];
        $fakeLogs[0]->expects($this->once())->method('setStatus')->with('READED');
        $fakeLogs[1]->expects($this->once())->method('setStatus')->with('READED');
        $this->logRepositoryMock->method('findBy')->with(['status' => 'UNREADED'])->willReturn($fakeLogs);

        // expect entity manager to flush changes
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->setAllLogsToReaded();
    }

    /**
     * Test truncate logs table
     *
     * @return void
     */
    public function testTruncateLogsTable(): void
    {
        // mock database name and table name
        $this->appUtilMock->method('getEnvValue')->with('DATABASE_NAME')->willReturn('test_db');

        // create a mock for ClassMetadata and set the table name
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->method('getTableName')->willReturn('logs');

        // mock getClassMetadata to return the ClassMetadata mock
        $this->entityManagerMock->method('getClassMetadata')->with(Log::class)->willReturn($classMetadataMock);

        // expect database manager to truncate the table
        $this->databaseManagerMock->expects($this->once())->method('tableTruncate')->with('test_db', 'logs');

        // mock saveLog to ensure it's called correctly
        $this->logManager->saveLog(
            name: 'log-manager',
            message: 'Logs table truncated in database: test_db',
            level: LogManager::LEVEL_CRITICAL
        );

        // call tested method
        $this->logManager->truncateLogsTable();
    }
}
