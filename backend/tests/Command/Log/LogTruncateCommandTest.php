<?php

namespace App\Tests\Command\Log;

use Exception;
use App\Manager\LogManager;
use PHPUnit\Framework\TestCase;
use App\Command\Log\LogTruncateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class LogTruncateCommandTest
 *
 * Test cases for logs table truncate command
 *
 * @package App\Tests\Command\Log
 */
class LogTruncateCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private LogManager & MockObject $logManager;

    protected function setUp(): void
    {
        // mock log manager
        $this->logManager = $this->createMock(LogManager::class);

        // init command instance
        $command = new LogTruncateCommand($this->logManager);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test execute command cancellation on user rejection
     *
     * @return void
     */
    public function testExecuteCommandCancelled(): void
    {
        // simulate user confirmation
        $this->commandTester->setInputs(['no']);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Operation cancelled.', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with successful log truncation
     *
     * @return void
     */
    public function testExecuteCommandSuccess(): void
    {
        // simulate user confirmation
        $this->commandTester->setInputs(['yes']);

        // mock truncate logs table method to be called
        $this->logManager->expects($this->once())->method('truncateLogsTable');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Logs table truncated.', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with log truncation failure
     *
     * @return void
     */
    public function testExecuteCommandFailure(): void
    {
        // simulate user confirmation
        $this->commandTester->setInputs(['yes']);

        // mock exception
        $this->logManager->expects($this->once())->method('truncateLogsTable')->willThrowException(new Exception('Database error'));

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error truncating logs table: Database error', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
