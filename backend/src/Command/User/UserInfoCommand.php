<?php

namespace App\Command\User;

use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserInfoCommand
 *
 * Command for getting user info
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:info', description: 'Get user info')]
class UserInfoCommand extends Command
{
    private UserManager $userManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(UserManager $userManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->userManager = $userManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
        parent::__construct();
    }

    /**
     * Configure command arguments and options
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username of the user');
    }

    /**
     * Execute user info command
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

        // get user info
        $userInfo = $this->userManager->getUserInfo($id);

        // format user info
        $username = $userInfo['username'];
        $roles = $userInfo['roles'];
        $registerTime = $userInfo['register-time'];
        $lastLoginTime = $userInfo['last-login-time'];
        $ipAddress = $userInfo['ip-address'];
        $status = $userInfo['status'];

        /** @var string $userAgent */
        $userAgent = $userInfo['user-agent'];

        // check if user agent found
        if ($userAgent == null) {
            $io->error('Error getting user agent.');
            return Command::INVALID;
        }

        // get browser name
        $browser = $this->visitorInfoUtil->getBrowserShortify($userAgent);

        // check if roles valid
        if ($roles == null || !is_countable($roles)) {
            $io->error('Error getting user roles.');
            return Command::INVALID;
        }

        // select last role
        $role = $roles[count($roles) - 1];
        $role = str_replace('ROLE_', '', $role);

        // print user info
        $io->table(
            headers: ['Username', 'Roles', 'Register Time', 'Last Login Time', 'Ip Address', 'Browser', 'Status'],
            rows: [
                [
                    $username,
                    $role,
                    $registerTime,
                    $lastLoginTime,
                    $ipAddress,
                    $browser,
                    $status
                ]
            ]
        );

        return Command::SUCCESS;
    }
}
