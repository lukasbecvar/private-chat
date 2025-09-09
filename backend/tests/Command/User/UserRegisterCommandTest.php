<?php

namespace App\Tests\Command\User;

use Exception;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserRegisterCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserRegisterCommandTest
 *
 * Test cases for the user register command
 *
 * @package App\Tests\Command\User
 */
class UserRegisterCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserManager & MockObject $userManager;
    private UserRegisterCommand $userRegisterCommand;
    private ValidatorInterface & MockObject $validator;

    public function setUp(): void
    {
        // mock dependencies
        $this->userManager = $this->createMock(UserManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        // init command instance
        $this->userRegisterCommand = new UserRegisterCommand($this->userManager, $this->validator);
        $this->commandTester = new CommandTester($this->userRegisterCommand);
    }

    /**
     * Test execute command with empty email input
     *
     * @return void
     */
    public function testExecuteCommandEmptyEmailInput(): void
    {
        // simulate empty input
        $this->commandTester->setInputs(['', 'John', 'Doe', 'password']);

        // execute command
        $exitCode = $this->commandTester->execute([]);

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
        // simulate invalid email input
        $this->commandTester->setInputs(['invalid_email', 'John', 'Doe', 'password']);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Invalid email format.', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command when user already exists
     *
     * @return void
     */
    public function testExecuteCommandUserExists(): void
    {
        // simulate user input
        $this->commandTester->setInputs(['test@test.com', 'John', 'Doe', 'password']);

        // mock user existence check
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(true);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('User already exists: test@test.com', $output);
        $this->assertEquals(Command::INVALID, $exitCode);
    }

    /**
     * Test execute command with successful registration
     *
     * @return void
     */
    public function testExecuteCommandSuccess(): void
    {
        // simulate user input
        $this->commandTester->setInputs(['test@test.com', 'John', 'Doe', 'password']);

        // mock successful registration
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(false);
        $this->userManager->expects($this->once())->method('registerUser')->with('test@test.com', 'John', 'Doe', 'password');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('User registered: test@test.com (John Doe)', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with registration failure
     *
     * @return void
     */
    public function testExecuteCommandFailure(): void
    {
        // simulate user input
        $this->commandTester->setInputs(['test@test.com', 'John', 'Doe', 'password']);

        // mock exception during registration
        $this->userManager->expects($this->once())->method('checkIfUserEmailAlreadyRegistered')->with('test@test.com')->willReturn(false);
        $this->userManager->expects($this->once())->method('registerUser')->willThrowException(new Exception('Database error'));

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error registering user: Database error', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
