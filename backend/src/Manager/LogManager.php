<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\SecurityUtil;
use App\Util\VisitorInfoUtil;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LogManager
 *
 * Manager for log system functionality (logging to database)
 *
 * @package App\Manager
 */
class LogManager
{
    // log levels definitions
    public const LEVEL_CRITICAL = 1;
    public const LEVEL_WARNING = 2;
    public const LEVEL_NOTICE = 3;
    public const LEVEL_INFO = 4;

    private AppUtil $appUtil;
    private Security $security;
    private SecurityUtil $securityUtil;
    private ErrorManager $errorManager;
    private LogRepository $logRepository;
    private DatabaseManager $databaseManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        Security $security,
        SecurityUtil $securityUtil,
        ErrorManager $errorManager,
        LogRepository $logRepository,
        DatabaseManager $databaseManager,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->security = $security;
        $this->securityUtil = $securityUtil;
        $this->errorManager = $errorManager;
        $this->logRepository = $logRepository;
        $this->entityManager = $entityManager;
        $this->databaseManager = $databaseManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Save log to database
     *
     * @param string $name The log name
     * @param string $message The log message
     * @param int $level The log level
     *
     * @throws Exception Error to persist or flush log to database
     *
     * @return void
     */
    public function saveLog(string $name, string $message, int $level = self::LEVEL_INFO): void
    {
        // check if database logging is enabled
        if (!$this->appUtil->isDatabaseLoggingEnabled()) {
            return;
        }

        // check if log can be saved in database
        if (str_contains($message, 'Connection refused')) {
            return;
        }

        // check required log level
        if ($level > (int) $this->appUtil->getEnvValue('LOG_LEVEL')) {
            return;
        }

        // escape log message
        $name = $this->securityUtil->escapeString($name);
        $message = $this->securityUtil->escapeString($message);

        // check if name or message is null
        if ($name == null || $message == null) {
            $this->errorManager->handleError(
                message: 'Error to get or escape log name or message',
                code: JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // get log time
        $time = new DateTime();

        // get request data
        $requestUri = $this->appUtil->getRequestUri() ?? 'Unknown';
        $requestMethod = $this->appUtil->getRequestMethod() ?? 'Unknown';

        // get info of current user
        $userAgent = $this->visitorInfoUtil->getUserAgent() ?? 'Unknown';
        $userIp = $this->visitorInfoUtil->getIP() ?? 'Unknown';

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        // get user id
        if ($user === null) {
            $userId = 0;
        } else {
            $userId = $user->getId() ?? 0;
        }

        // create log entity
        $log = new Log();
        $log->setName($name)
            ->setMessage($message)
            ->setTime($time)
            ->setUserAgent($userAgent)
            ->setRequestUri($requestUri)
            ->setRequestMethod($requestMethod)
            ->setIpAddress($userIp)
            ->setLevel($level)
            ->setUserId($userId)
            ->setStatus('UNREADED');

        try {
            // save log to database
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error save log to database',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }

    /**
     * Get logs by status with pagination
     *
     * @param string $status The status of the logs
     * @param int $page The page number
     * @param int $paginationLimit The pagination limit (default: get from env value LIMIT_CONTENT_PER_PAGE)
     *
     * @return array<Log> Logs list
     */
    public function getLogsByStatus(string $status, int $page, ?int $paginationLimit = null): array
    {
        // pagination limit
        if ($paginationLimit === null) {
            $paginationLimit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');
        }

        // get logs by status from database
        $logs = $this->logRepository->findByStatus($status, $page, $paginationLimit);
        return $logs;
    }

    /**
     * Get logs by user id with pagination
     *
     * @param int $userId The user id
     * @param int $page The page number
     * @param int $paginationLimit The pagination limit (default: get from env value LIMIT_CONTENT_PER_PAGE)
     *
     * @return array<Log> Logs list
     */
    public function getLogsByUserId(int $userId, int $page, ?int $paginationLimit = null): array
    {
        // pagination limit
        if ($paginationLimit === null) {
            $paginationLimit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');
        }

        // get logs by user id from database
        $logs = $this->logRepository->findByUserId($userId, $page, $paginationLimit);
        return $logs;
    }

    /**
     * Get logs by ip address with pagination
     *
     * @param string $ipAddress The ip address
     * @param int $page The page number
     * @param int $paginationLimit The pagination limit (default: get from env value LIMIT_CONTENT_PER_PAGE)
     *
     * @return array<Log> Logs list
     */
    public function getLogsByIpAddress(string $ipAddress, int $page, ?int $paginationLimit = null): array
    {
        // pagination limit
        if ($paginationLimit === null) {
            $paginationLimit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');
        }

        // get logs by ip address from database
        $logs = $this->logRepository->findByIpAddress($ipAddress, $page, $paginationLimit);
        return $logs;
    }

    /**
     * Get logs by criteria with username
     *
     * @param array<string, mixed> $criteria The search criteria
     * @param int $page The page number
     * @param int|null $paginationLimit The limit of logs per page (default: get from env value LIMIT_CONTENT_PER_PAGE)
     *
     * @return array<mixed> Logs list
     */
    public function getLogsWithUsername(array $criteria, int $page, ?int $paginationLimit = null): array
    {
        // pagination limit
        if ($paginationLimit === null) {
            $paginationLimit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');
        }

        // get logs by criteria from database
        return $this->logRepository->findLogsWithUsername($criteria, $page, $paginationLimit);
    }

    /**
     * Update log status by log id
     *
     * @param int $id The log id
     * @param string $status The log status
     *
     * @throws Exception Error to update log status
     *
     * @return void
     */
    public function updateLogStatus(int $id, string $status): void
    {
        // get log by id
        $log = $this->logRepository->find($id);

        // check if log is found
        if ($log === null) {
            $this->errorManager->handleError(
                message: 'Error to get log by id: ' . $id,
                code: Response::HTTP_NOT_FOUND
            );
        }

        // update log status
        $log->setStatus($status);

        try {
            // flush changes to database
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to update log status',
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }

    /**
     * Set all logs with status 'UNREADED' to 'READED'
     *
     * This method fetches logs with status 'UNREADED' and
     * updates their status to 'READED', and flushes changes to the database
     *
     * @throws Exception Error to flush update to database
     *
     * @return void
     */
    public function setAllLogsToReaded(): void
    {
        /** @var array<Log> $logs */
        $logs = $this->logRepository->findBy(['status' => 'UNREADED']);

        if (is_iterable($logs)) {
            // set all logs to readed status
            foreach ($logs as $log) {
                $log->setStatus('READED');
            }
        }

        try {
            // flush changes to database
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to set all logs status to "READED"',
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }

    /**
     * Truncate logs table
     *
     * This method truncates the logs table in the database
     *
     * @throws Exception Error truncating table
     *
     * @return void
     */
    public function truncateLogsTable(): void
    {
        // get database name
        $dbName = $this->appUtil->getEnvValue('DATABASE_NAME');

        // get logs database table name
        $classMetadata = $this->entityManager->getClassMetadata(Log::class);
        $tableName = $classMetadata->getTableName();

        // truncate logs table
        $this->databaseManager->tableTruncate($dbName, $tableName);

        // log truncate success
        $this->saveLog(
            name: 'log-manager',
            message: 'Logs table truncated in database: ' . $dbName,
            level: self::LEVEL_CRITICAL
        );
    }
}
