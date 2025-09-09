<?php

namespace App\Command\Log;

use App\Manager\LogManager;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class LogReaderCommand
 *
 * Command for reading logs based on the specified filter
 *
 * @package App\Command\Log
 */
#[AsCommand(name: 'app:log:reader', description: 'Reads logs based on the specified filter')]
class LogReaderCommand extends Command
{
    private LogManager $logManager;
    private UserManager $userManager;

    public function __construct(LogManager $logManager, UserManager $userManager)
    {
        $this->logManager = $logManager;
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
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Filter by status')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'Filter by user email')
            ->addOption('ip', null, InputOption::VALUE_OPTIONAL, 'Filter by IP address')
            ->setHelp(<<<'HELP'
                Usage: 

                <fg=green>app:log:reader --status=<status></>  Filter by status (READED, UNREADED)
                <fg=green>app:log:reader --user=<user></>      Filter by user (email)
                <fg=green>app:log:reader --ip=<ip></>          Filter by IP address (192.168.1.1)

                <comment>Note:</comment> Only one of these parameters can be used at a time.
                HELP
            )
        ;
    }

    /**
     * Execute log reader command
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
        $status = $input->getOption('status');
        $user = $input->getOption('user');
        $ip = $input->getOption('ip');

        // build options array
        $options = array_filter([
            'status' => $status,
            'user' => $user,
            'ip' => $ip
        ]);

        // init logs array
        $logs = [];

        // check if any options are set
        if (count($options) === 0) {
            $io->error('You must specify one parameter (--status, --user, or --ip).');
            return Command::INVALID;
        }

        // check if only one option is set
        if (count($options) > 1) {
            throw new InvalidArgumentException('You can only use one parameter at a time.');
        }

        // init search criteria array
        $criteria = [];

        // get logs by status
        if ($status !== null) {
            if ($status == 'unreaded') {
                $status = 'UNREADED';
            }
            if ($status == 'readed') {
                $status = 'READED';
            }
            $criteria = ['status' => $status];

        // get logs by user
        } elseif ($user !== null) {
            // check if user found in database
            if (!$this->userManager->checkIfUserEmailAlreadyRegistered($user)) {
                $io->error('User not found in database.');
                return Command::INVALID;
            }

            // get user id by email
            $userId = $this->userManager->getUserIdByEmail($user);
            $criteria = ['user_id' => $userId];

        // get logs by ip address
        } elseif ($ip !== null) {
            $criteria = ['ip_address' => $ip];
        }

        // get logs
        $logs = $this->logManager->getLogsWithUsername($criteria, 1, PHP_INT_MAX);

        // check if logs are found
        if (count($logs) === 0) {
            $io->error('No logs found for your specified filter.');
            return Command::INVALID;
        }

        // reverse logs array (sort by id ASC)
        $logs = array_reverse($logs);

        $data = [];
        foreach ($logs as $log) {
            // format time
            $time = $log['time'];
            $formattedTime = $time ? $time->format('Y-m-d H:i:s') : 'N/A';

            // add log to data array
            $data[] = [
                $log['id'],
                $log['name'],
                $log['message'],
                $formattedTime,
                $log['ip_address'],
                $log['username'] ?? 'Guest'
            ];
        }

        // render logs table
        $io->table(
            headers: ['#', 'Name', 'Message', 'time', 'Ip Address', 'User'],
            rows: $data
        );

        return Command::SUCCESS;
    }
}
