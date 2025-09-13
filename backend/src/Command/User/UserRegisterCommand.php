<?php

namespace App\Command\User;

use Exception;
use App\DTO\UserDTO;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserRegisterCommand
 *
 * Command for registering new user
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:register', description: 'Register user')]
class UserRegisterCommand extends Command
{
    private UserManager $userManager;
    private ValidatorInterface $validator;

    public function __construct(UserManager $userManager, ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->userManager = $userManager;
        parent::__construct();
    }

    /**
     * Execute user registration command
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get visitor info for cli mode
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'CLI-COMMAND';

        // get username from cli input
        $username = $io->ask('Enter user username');
        if ($username == null) {
            $io->error('Username cannot be empty.');
            return Command::INVALID;
        }

        // get password from cli input
        $password = $io->askHidden('Enter password (hidden input)');
        if ($password == null) {
            $io->error('Password cannot be empty.');
            return Command::INVALID;
        }

        // create user dto
        $userDTO = new UserDTO();
        $userDTO->username = $username;
        $userDTO->password = $password;

        // validate input data
        $errors = $this->validator->validate($userDTO);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $io->error($error->getPropertyPath() . ': ' . $error->getMessage());
            }
            return Command::INVALID;
        }

        // check if user already exists
        if ($this->userManager->checkIfUserIsAlreadyRegistered($username)) {
            $io->error('User already exists: ' . $username);
            return Command::INVALID;
        }

        // register user
        try {
            $this->userManager->registerUser(
                $userDTO->username,
                $userDTO->password
            );
            $io->success('User registered: ' . $username);
        } catch (Exception $e) {
            $io->error('Error registering user: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
