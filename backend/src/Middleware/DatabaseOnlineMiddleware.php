<?php

namespace App\Middleware;

use Exception;
use App\Manager\CacheManager;
use App\Manager\ErrorManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseOnlineMiddleware
 *
 * Middleware for checking database connection
 *
 * @package App\Middleware
 */
class DatabaseOnlineMiddleware
{
    private Connection $connection;
    private ErrorManager $errorManager;
    private CacheManager $cacheManager;

    public function __construct(Connection $connection, ErrorManager $errorManager, CacheManager $cacheManager)
    {
        $this->connection = $connection;
        $this->errorManager = $errorManager;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Check database connection status
     *
     * @throws Exception Database connection error
     *
     * @return void
     */
    public function onKernelRequest(): void
    {
        // check if redis connection is ok
        if (!$this->cacheManager->isRedisConnected()) {
            $this->errorManager->handleError(
                message: 'Redis connection error',
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: 'redis connection error'
            );
        }

        try {
            // select for connection try
            $this->connection->executeQuery('SELECT 1');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Database connection error',
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }
}
