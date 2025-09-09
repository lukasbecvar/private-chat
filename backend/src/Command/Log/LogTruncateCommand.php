<?php

namespace App\Command\Log;

use Exception;
use App\Manager\LogManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogTruncateCommand
 *
 * Command for truncating logs table
 *
 * @package App\Command\Log
 */
#[AsCommand(name: 'app:log:truncate', description: 'Truncate logs table')]
class LogTruncateCommand extends Command
{
    private LogManager $logManager;

    public function __construct(LogManager $logManager)
    {
        $this->logManager = $logManager;
        parent::__construct();
    }

    /**
     * Execute log truncate command
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

        // confirmation prompt
        if (!$io->confirm('Are you sure you want to truncate the logs table? This action cannot be undone.', false)) {
            $io->warning('Operation cancelled.');
            return Command::FAILURE;
        }

        // truncate logs table
        try {
            $this->logManager->truncateLogsTable();
            $io->success('Logs table truncated.');
        } catch (Exception $e) {
            $io->error('Error truncating logs table: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
