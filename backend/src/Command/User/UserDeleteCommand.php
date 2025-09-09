<?php

namespace App\Command\User;

use Exception;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserDeleteCommand
 *
 * Command for deleting user from database
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:delete', description: 'Delete user')]
class UserDeleteCommand extends Command
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
        parent::__construct();
    }

    /**
     * Configure command arguments and options
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the user to delete');
    }

    /**
     * Execute user delete command
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

        // get email argument
        $email = $input->getArgument('email');

        // validate email input
        if ($email == null) {
            $io->error('Email cannot be empty.');
            return Command::INVALID;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Invalid email format.');
            return Command::INVALID;
        }

        // check if user exists
        if (!$this->userManager->checkIfUserEmailAlreadyRegistered($email)) {
            $io->error('User not found: ' . $email);
            return Command::INVALID;
        }

        // get user id by email
        $id = $this->userManager->getUserIdByEmail($email);

        // delete user
        try {
            $this->userManager->deleteUser($id);
            $io->success('User ' . $email . ' deleted.');
        } catch (Exception $e) {
            $io->error('Error deleting user: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
