<?php

namespace App\Tests\Command\User;

use Exception;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserUpdateRoleCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserUpdateRoleCommandTest
 *
 * Test cases for update user role command
 *
 * @package App\Tests\Command\User
 */
class UserUpdateRoleCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserUpdateRoleCommand $command;
    private UserManager & MockObject $userManager;

    public function setUp(): void
    {
        // mock dependencies
        $this->userManager = $this->createMock(UserManager::class);

        // init command instance
        $this->command = new UserUpdateRoleCommand($this->userManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command without user email input
     *
     * @return void
     */
    public function testExecuteCommandWithoutUserEmailInput(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('The --user option is required.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with missing role action
     *
     * @return void
     */
    public function testExecuteMissingRoleAction(): void
    {
        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // execute command
        $exitCode = $this->commandTester->execute(['--user' => 'test@test.com']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('No role action provided. Use --add or --remove.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with non-existing user
     *
     * @return void
     */
    public function testExecuteCommandWithUserNotFound(): void
    {
        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(false);

        // execute command
        $exitCode = $this->commandTester->execute(['--user' => 'test@test.com', '--add' => 'ROLE_ADMIN']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('User not found: test@test.com', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with successful role addition
     *
     * @return void
     */
    public function testExecuteCommandWithSuccessfulRoleAddition(): void
    {
        // mock get user id by email
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn(1);

        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // expect add role to user call
        $this->userManager->expects($this->once())->method('addRoleToUser')->with(1, 'ROLE_ADMIN');

        // execute command
        $exitCode = $this->commandTester->execute(['--user' => 'test@test.com', '--add' => 'ROLE_ADMIN']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString("Role: ROLE_ADMIN added to user: test@test.com.", $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with successful role removal
     *
     * @return void
     */
    public function testExecuteCommandWithSuccessfulRoleRemoval(): void
    {
        // mock get user id by email
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn(1);

        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // expect remove role from user call
        $this->userManager->expects($this->once())->method('removeRoleFromUser')->with(1, 'ROLE_ADMIN');

        // execute command
        $exitCode = $this->commandTester->execute(['--user' => 'test@test.com', '--remove' => 'ROLE_ADMIN']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString("Role: ROLE_ADMIN removed from user: test@test.com.", $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with exception response
     *
     * @return void
     */
    public function testExecuteCommandWithExceptionResponse(): void
    {
        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // mock add role to user method to throw exception
        $this->userManager->expects($this->once())->method('addRoleToUser')->willThrowException(new Exception('Database error'));

        // execute command
        $exitCode = $this->commandTester->execute(['--user' => 'test@test.com', '--add' => 'ROLE_ADMIN']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error updating user role: Database error', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
