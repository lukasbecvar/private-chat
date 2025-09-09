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
 * Class UserStatusUpdateCommand
 *
 * Command for updating user status
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:status:update', description: 'Update user status')]
class UserStatusUpdateCommand extends Command
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        parent::__construct();
        $this->userManager = $userManager;
    }

    /**
     * Configure command arguments and options
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the user to delete');
        $this->addArgument('status', InputArgument::REQUIRED, 'New status of the user');
    }

    /**
     * Execute user status update command
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

        // get command arguments
        $email = $input->getArgument('email');
        $status = $input->getArgument('status');

        // validate email input
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Invalid email format.');
            return Command::INVALID;
        }

        // check if user exists
        if (!$this->userManager->checkIfUserEmailAlreadyRegistered($email)) {
            $io->error('User not found.');
            return Command::INVALID;
        }

        // get user id by email
        $id = $this->userManager->getUserIdByEmail($email);

        // check if user status already associated with user
        if ($this->userManager->getUserStatus($id) === $status) {
            $io->error('User status already set to: ' . $status);
            return Command::INVALID;
        }

        // update user status
        try {
            $this->userManager->updateUserStatus($id, $status);
            $io->success('User status updated.');
        } catch (Exception $e) {
            $io->error('Error updating user status: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
