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
        $this->addArgument('username', InputArgument::REQUIRED, 'Username of the user to delete');
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

        // get username argument
        $username = $input->getArgument('username');

        // validate username input
        if ($username == null) {
            $io->error('Username cannot be empty.');
            return Command::INVALID;
        }

        // check if user exists
        if (!$this->userManager->checkIfUserIsAlreadyRegistered($username)) {
            $io->error('User not found: ' . $username);
            return Command::INVALID;
        }

        // get user id by username
        $id = $this->userManager->getUserIdByUsername($username);

        // delete user
        try {
            $this->userManager->deleteUser($id);
            $io->success('User ' . $username . ' deleted.');
        } catch (Exception $e) {
            $io->error('Error deleting user: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
