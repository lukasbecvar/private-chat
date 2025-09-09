<?php

namespace App\Command\User;

use Exception;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserUpdateRoleCommand
 *
 * Command for updating user role
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:role:update', description: 'Add or remove user role')]
class UserUpdateRoleCommand extends Command
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
        $this
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Email of the user to update')
            ->addOption('add', null, InputOption::VALUE_OPTIONAL, 'Role to add to the user')
            ->addOption('remove', null, InputOption::VALUE_OPTIONAL, 'Role to remove from the user')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command allows you to add or remove roles from a user:

                Examples:
                - Add a role:
                    <info>php bin/console %command.name% --user="test@test.test" --add="ROLE_ADMIN"</info>
                
                - Remove a role:
                    <info>php bin/console %command.name% --user="test@test.test" --remove="ROLE_ADMIN"</info>

                - If no role action (--add or --remove) is provided, the command will display an error.

                Options:
                --user      (required) Email of the user to update
                --add       (optional) Role to add
                --remove    (optional) Role to remove
            HELP)
        ;
    }

    /**
     * Execute user role update command
     *
     * @param InputInterface $input The command input
     * @param OutputInterface $output The command output
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get visitor info for cli mode
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'CLI-COMMAND';

        // get command options
        $email = $input->getOption('user');
        $roleToAdd = $input->getOption('add');
        $roleToRemove = $input->getOption('remove');

        // check if user email is provided
        if (!$email) {
            $io->error('The --user option is required.');
            return Command::INVALID;
        }

        // check if user exists
        if (!$this->userManager->checkIfUserEmailAlreadyRegistered($email)) {
            $io->error('User not found: ' . $email);
            return Command::INVALID;
        }

        // check if add or remove is provided
        if (!$roleToAdd && !$roleToRemove) {
            $io->warning('No role action provided. Use --add or --remove.');
            return Command::INVALID;
        }

        // get user id by email
        $id = $this->userManager->getUserIdByEmail($email);

        try {
            // add role to user
            if ($roleToAdd) {
                $this->userManager->addRoleToUser($id, $roleToAdd);
                $io->success('Role: ' . $roleToAdd . ' added to user: ' . $email . '.');
            }

            // remove role from user
            if ($roleToRemove) {
                $this->userManager->removeRoleFromUser($id, $roleToRemove);
                $io->success('Role: ' . $roleToRemove . ' removed from user: ' . $email . '.');
            }
        } catch (Exception $e) {
            $io->error('Error updating user role: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
