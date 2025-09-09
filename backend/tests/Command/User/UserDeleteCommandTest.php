<?php

namespace App\Tests\Command\User;

use Exception;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserDeleteCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserDeleteCommandTest
 *
 * Test cases for user delete command
 *
 * @package App\Tests\Command\User
 */
class UserDeleteCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserDeleteCommand $userDeleteCommand;
    private UserManager & MockObject $userManager;

    public function setUp(): void
    {
        // mock dependencies
        $this->userManager = $this->createMock(UserManager::class);

        // init command instance
        $this->userDeleteCommand = new UserDeleteCommand($this->userManager);
        $this->commandTester = new CommandTester($this->userDeleteCommand);
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
     * Test execute command with successful user deletion
     *
     * @return void
     */
    public function testExecuteCommandSuccess(): void
    {
        // testing user id
        $id = 1;

        // mock user manager to return the user id
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn($id);

        // expect delete user call
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);
        $this->userManager->expects($this->once())->method('deleteUser')->with($id);

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString("User test@test.com deleted.", $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with failure in deleting user
     *
     * @return void
     */
    public function testExecuteCommandFailure(): void
    {
        // testing user id
        $id = 1;

        // mock user manager to return the user id
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn($id);

        // mock exception during user deletion throw exception
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);
        $this->userManager->expects($this->once())->method('deleteUser')->with($id)->willThrowException(new Exception('Database error'));

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error deleting user: Database error', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
