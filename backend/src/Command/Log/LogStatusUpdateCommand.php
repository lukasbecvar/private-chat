<?php

namespace App\Command\Log;

use App\Manager\LogManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogStatusUpdateCommand
 *
 * Command for update log status
 *
 * @package App\Command\Log
 */
#[AsCommand(name: 'app:log:status:update', description: 'Update log status')]
class LogStatusUpdateCommand extends Command
{
    private LogManager $logManager;

    public function __construct(LogManager $logManager)
    {
        $this->logManager = $logManager;
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
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Log ID to update or "all" to mark all logs as read')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'New status for the log (required when --id is not "all")')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command updates the status of logs:

                <info>php %command.full_name% --id=123 --status=new</info>
                Updates the log with ID 123 to the status "new".

                <info>php %command.full_name% --id=all</info>
                Marks all logs as read.

                If you use --id=all, the --status option must be omitted.
                HELP
            )
        ;
    }

    /**
     * Execute log status update command
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
        $id = $input->getOption('id');
        $status = $input->getOption('status');

        // check if id parameter is used
        if ($id === null) {
            $io->error('The --id option is required.');
            return Command::INVALID;
        }

        // check if status parameter is used
        if ($id === 'all' && $status !== null) {
            $io->error('You cannot use --status when --id is "all".');
            return Command::INVALID;
        }

        // check if id is valid
        if ($id !== 'all' && (!ctype_digit($id) || $status === null)) {
            $io->error('When --id is not "all", you must provide a valid integer ID and --status.');
            return Command::INVALID;
        }

        // check if status is valid
        if ($id !== 'all' && empty($status)) {
            $io->error('When --id is not "all", you must provide a valid status.');
            return Command::INVALID;
        }

        // execute status update
        if ($id === 'all') {
            $this->logManager->setAllLogsToReaded();
            $io->success('All logs have been marked as read.');
        } else {
            $this->logManager->updateLogStatus((int)$id, $status);
            $io->success('Log with ID ' . $id . ' has been updated to status "' . $status . '"');
        }

        return Command::SUCCESS;
    }
}
