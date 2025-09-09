<?php

namespace App\Tests\Command\User;

use Exception;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Command\User\UserStatusUpdateCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserStatusUpdateCommandTest
 *
 * Test cases for user status update command
 *
 * @package App\Tests\Command\User
 */
class UserStatusUpdateCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserManager & MockObject $userManager;
    private UserStatusUpdateCommand $userStatusUpdateCommand;

    public function setUp(): void
    {
        // mock dependencies
        $this->userManager = $this->createMock(UserManager::class);

        // init command instance
        $this->userStatusUpdateCommand = new UserStatusUpdateCommand($this->userManager);
        $this->commandTester = new CommandTester($this->userStatusUpdateCommand);
    }

    /**
     * Test execute command with empty email input
     *
     * @return void
     */
    public function testExecuteCommandEmptyEmailInput(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['email' => '', 'status' => 'inactive']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Invalid email format.', $output);
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
        $exitCode = $this->commandTester->execute(['email' => 'invalid_email', 'status' => 'inactive']);

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
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com', 'status' => 'inactive']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('User not found.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command when user status is already set
     *
     * @return void
     */
    public function testExecuteCommandWhenStatusIsAlreadySet(): void
    {
        // testing user id
        $id = 1;
        $status = 'inactive';

        // mock user manager to return the user id
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn($id);

        // mock user manager to return the user status
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);
        $this->userManager->expects($this->once())->method('getUserStatus')->with($id)->willReturn($status);

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com', 'status' => $status]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('User status already set to: ' . $status, $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with successful user status update
     *
     * @return void
     */
    public function testExecuteCommandSuccess(): void
    {
        // testing user id
        $id = 1;
        $status = 'inactive';

        // mock user manager to return the user id
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn($id);

        // expect checkIfUserEmailAlreadyRegistered and updateUserStatus calls
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);
        $this->userManager->expects($this->once())->method('updateUserStatus')->with($id, $status);

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com', 'status' => $status]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString("User status updated.", $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with failure in updating user status
     *
     * @return void
     */
    public function testExecuteCommandFailure(): void
    {
        // testing user id
        $id = 1;
        $status = 'inactive';

        // mock user manager to return the user id
        $this->userManager->expects($this->once())->method('getUserIdByEmail')->with('test@test.com')->willReturn($id);

        // mock exception during user status update
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);
        $this->userManager->expects($this->once())->method('updateUserStatus')->with($id, $status)->willThrowException(new Exception('Database error'));

        // execute command
        $exitCode = $this->commandTester->execute(['email' => 'test@test.com', 'status' => $status]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error updating user status: Database error', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
