<?php

namespace App\Tests\Command\Log;

use DateTime;
use App\Manager\LogManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\Log\LogReaderCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class LogReaderCommandTest
 *
 * Test cases for log reader command
 *
 * @package App\Tests\Command\Log
 */
class LogReaderCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private LogReaderCommand $logReaderCommand;
    private LogManager & MockObject $logManager;
    private UserManager & MockObject $userManager;

    public function setUp(): void
    {
        // mock dependencies
        $this->logManager = $this->createMock(LogManager::class);
        $this->userManager = $this->createMock(UserManager::class);

        // init command instance
        $this->logReaderCommand = new LogReaderCommand($this->logManager, $this->userManager);
        $this->commandTester = new CommandTester($this->logReaderCommand);
    }

    /**
     * Test execute command without parameters
     *
     * @return void
     */
    public function testExecuteCommandWithoutParameters(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('You must specify one parameter (--status, --user, or --ip).', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with multiple parameters
     *
     * @return void
     */
    public function testExecuteCommandWithMultipleParameters(): void
    {
        // expect exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You can only use one parameter at a time.');

        // execute command
        $this->commandTester->execute(['--status' => 'READED', '--user' => '123']);
    }

    /**
     * Test execute command with no logs found
     *
     * @return void
     */
    public function testExecuteCommandWithNoLogsFound(): void
    {
        // mock get log methods to simulate no logs found
        $this->logManager->method('getLogsWithUsername')->willReturn([]);

        // execute command
        $exitCode = $this->commandTester->execute(['--status' => 'READED']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('No logs found for your specified filter.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command get logs by status with success response
     *
     * @return void
     */
    public function testExecuteCommandGetLogsByStatusWithSuccessResponse(): void
    {
        // mock log data
        $logData = [
            'id' => 1,
            'name' => 'Test Log',
            'message' => 'Test message',
            'time' => new DateTime('2024-12-07 10:00:00'),
            'ip_address' => '192.168.1.1',
            'username' => 'test@example.com'
        ];

        // mock get log methods to return the mocked log data
        $this->logManager->method('getLogsWithUsername')->willReturn([$logData]);

        // execute command
        $exitCode = $this->commandTester->execute(['--status' => 'READED']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Test Log', $output);
        $this->assertStringContainsString('Test message', $output);
        $this->assertStringContainsString('test@example.com', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command get logs by user with success response
     *
     * @return void
     */
    public function testExecuteCommandGetLogsByUserWithSuccessResponse(): void
    {
        // mock log data
        $logData = [
            'id' => 1,
            'name' => 'Test Log',
            'message' => 'Test message',
            'time' => new DateTime('2024-12-07 10:00:00'),
            'ip_address' => '192.168.1.1',
            'username' => 'user@example.com'
        ];

        // mock user manager to return true for user check
        $this->userManager->method('checkIfUserEmailAlreadyRegistered')->willReturn(true);
        $this->userManager->method('getUserIdByEmail')->willReturn(123);

        // mock get log methods to return the mocked log data
        $this->logManager->method('getLogsWithUsername')->willReturn([$logData]);

        // execute command
        $exitCode = $this->commandTester->execute(['--user' => 'user@example.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Test Log', $output);
        $this->assertStringContainsString('Test message', $output);
        $this->assertStringContainsString('user@example.com', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
