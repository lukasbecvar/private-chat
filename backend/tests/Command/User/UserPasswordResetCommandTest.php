<?php

namespace App\Tests\Command\User;

use Exception;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use App\Command\User\UserPasswordResetCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserPasswordResetCommandTest
 *
 * Test cases for user password reset command
 *
 * @package App\Tests\Command\User
 */
class UserPasswordResetCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserManager & MockObject $userManager;
    private UserPasswordResetCommand $userPasswordResetCommand;

    public function setUp(): void
    {
        // mock dependencies
        $this->userManager = $this->createMock(UserManager::class);

        // init command instance
        $this->userPasswordResetCommand = new UserPasswordResetCommand($this->userManager);
        $this->commandTester = new CommandTester($this->userPasswordResetCommand);
    }

    /**
     * Test execute command with empty email input
     *
     * @return void
     */
    public function testExecuteCommandEmptyEmailInput(): void
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
    public function testExecuteCommandInvalidEmail(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'invalid_email']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Invalid email format.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command when user does not exist
     *
     * @return void
     */
    public function testExecuteCommandUserNotFound(): void
    {
        // simulate user not found
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
     * Test execute command with successful user password reset
     *
     * @return void
     */
    public function testExecuteCommandSuccess(): void
    {
        $id = 1;
        $newPassword = 'new-password';

        // simulate user found
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // mock user manager to return the user id
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn($id);

        // expect reset user password call
        $this->userManager->expects($this->once())->method('resetUserPassword')->with($id)->willReturn($newPassword);

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('User password reset: test@test.com the new password is: ' . $newPassword, $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with failure in resetting user password
     *
     * @return void
     */
    public function testExecuteCommandFailure(): void
    {
        $id = 1;

        // simulate user found
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // mock user manager to return the user id
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn($id);

        // simulate exception during user password reset
        $this->userManager->expects($this->once())->method('resetUserPassword')->with($id)->willThrowException(new Exception('Reset error'));

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error resetting user password: Reset error', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
