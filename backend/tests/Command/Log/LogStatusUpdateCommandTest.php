<?php

namespace App\Tests\Command\Log;

use App\Manager\LogManager;
use PHPUnit\Framework\TestCase;
use App\Command\Log\LogStatusUpdateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class LogStatusUpdateCommandTest
 *
 * Test cases for log status update command
 *
 * @package App\Tests\Command\Log
 */
class LogStatusUpdateCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private LogManager & MockObject $logManager;
    private LogStatusUpdateCommand $logStatusUpdateCommand;

    public function setUp(): void
    {
        // mock log manager
        $this->logManager = $this->createMock(LogManager::class);

        // init command instance
        $this->logStatusUpdateCommand = new LogStatusUpdateCommand($this->logManager);
        $this->commandTester = new CommandTester($this->logStatusUpdateCommand);
    }

    /**
     * Test executute command without parameters
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
        $this->assertStringContainsString('The --id option is required.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with --id=all and --status set
     *
     * @return void
     */
    public function testExecuteCommandWithIdAllAndStatus(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute([
            '--id' => 'all',
            '--status' => 'new',
        ]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('You cannot use --status when --id is "all".', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with invalid ID and missing status
     *
     * @return void
     */
    public function testExecuteCommandWithInvalidIdAndMissingStatus(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute([
            '--id' => 'invalid',
        ]);

        // assert response
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with --id=all (success)
     *
     * @return void
     */
    public function testExecuteCommandWithIdAllSuccess(): void
    {
        // expect set all logs to readed status to be called
        $this->logManager->expects($this->once())->method('setAllLogsToReaded');

        // execute command
        $exitCode = $this->commandTester->execute([
            '--id' => 'all',
        ]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('All logs have been marked as read.', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with valid ID and status (success)
     *
     * @return void
     */
    public function testExecuteWithValidIdAndStatus(): void
    {
        // expect update log status to be called
        $this->logManager->expects($this->once())->method('updateLogStatus')->with(123, 'processed');

        // execute command
        $exitCode = $this->commandTester->execute([
            '--id' => '123',
            '--status' => 'processed',
        ]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Log with ID 123 has been updated to status "processed"', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
