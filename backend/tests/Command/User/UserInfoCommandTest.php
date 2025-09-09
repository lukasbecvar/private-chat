<?php

namespace App\Tests\Command\User;

use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserInfoCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserInfoCommandTest
 *
 * Test cases for user info command
 *
 * @package App\Tests\Command\User
 */
class UserInfoCommandTest extends TestCase
{
    private UserInfoCommand $command;
    private CommandTester $commandTester;
    private UserManager & MockObject $userManager;
    private VisitorInfoUtil & MockObject $visitorInfoUtil;

    public function setUp(): void
    {
        // mock dependencies
        $this->userManager = $this->createMock(UserManager::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);

        // init command instance
        $this->command = new UserInfoCommand($this->userManager, $this->visitorInfoUtil);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command without email input
     *
     * @return void
     */
    public function testExecuteCommandWithoutEmailInput(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['email' => '']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Email cannot be empty.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with invalid email format
     *
     * @return void
     */
    public function testExecuteCommandWithInvalidEmail(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'invalid-email']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Invalid email format.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with user not found
     *
     * @return void
     */
    public function testExecuteCommandWithUserNotFound(): void
    {
        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(false);

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('User not found: test@test.com', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with successful info retrieval
     *
     * @return void
     */
    public function testExecuteCommandWithSuccessfulInfoRetrieval(): void
    {
        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // mock user id get
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn(1);

        // mock user info get
        $this->userManager->expects($this->once())->method('getUserInfo')->with(1)->willReturn([
            'email' => 'test@test.com',
            'first-name' => 'John',
            'last-name' => 'Doe',
            'roles' => ['ROLE_USER'],
            'register-time' => '2022-01-01 12:00:00',
            'last-login-time' => '2023-01-01 12:00:00',
            'ip-address' => '127.0.0.1',
            'user-agent' => 'Mozilla/5.0',
            'status' => 'active'
        ]);

        // mock browser name get
        $this->visitorInfoUtil->expects($this->once())->method('getBrowserShortify')->with('Mozilla/5.0')->willReturn('Chrome');

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('test@test.com', $output);
        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('Doe', $output);
        $this->assertStringContainsString('Chrome', $output);
        $this->assertStringContainsString('active', $output);
        $this->assertStringContainsString('2022-01-01 12:00:00', $output);
        $this->assertStringContainsString('2023-01-01 12:00:00', $output);
        $this->assertStringContainsString('127.0.0.1', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with missing user agent
     *
     * @return void
     */
    public function testExecuteCommandWithMissingUserAgent(): void
    {
        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // mock user id get
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn(1);

        // mock user info get
        $this->userManager->expects($this->once())->method('getUserInfo')->with(1)->willReturn([
            'email' => 'test@test.com',
            'first-name' => 'John',
            'last-name' => 'Doe',
            'roles' => ['ROLE_USER'],
            'register-time' => '2022-01-01 12:00:00',
            'last-login-time' => '2023-01-01 12:00:00',
            'ip-address' => '127.0.0.1',
            'user-agent' => null,
            'status' => 'active'
        ]);

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error getting user agent.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }
}
