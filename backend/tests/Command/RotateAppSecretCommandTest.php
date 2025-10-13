<?php

namespace App\Tests\Command;

use Exception;
use App\Util\AppUtil;
use App\Manager\ChatManager;
use PHPUnit\Framework\TestCase;
use App\Command\RotateAppSecretCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RotateAppSecretCommandTest
 *
 * Test cases for execute secret key rotation command
 *
 * @package App\Tests\Command
 */
class RotateAppSecretCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private AppUtil & MockObject $appUtil;
    private ChatManager & MockObject $chatManager;
    private RotateAppSecretCommand $command;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->chatManager = $this->createMock(ChatManager::class);

        // initialize command instance
        $this->command = new RotateAppSecretCommand($this->appUtil, $this->chatManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command with exception
     *
     * @return void
     */
    public function testExecuteThrowsException(): void
    {
        // mock generateKey to throw exception
        $this->appUtil->method('generateKey')->willThrowException(new Exception('Something went wrong'));

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('Error during rotation: Something went wrong', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command successfully
     *
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        // mock generateKey
        $this->appUtil->expects($this->once())->method('generateKey')->with(16)->willReturn('new-secret-value');

        // mock updateEnvValue
        $this->appUtil->expects($this->once())->method('updateEnvValue')->with('APP_SECRET', 'new-secret-value');

        // expect re-encrypt chat messages
        $this->chatManager->expects($this->once())->method('reEncryptChatMessages');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('APP_SECRET has been rotated successfully', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
