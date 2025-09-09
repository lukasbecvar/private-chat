<?php

namespace App\Tests\Middleware;

use Exception;
use App\Manager\CacheManager;
use App\Manager\ErrorManager;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use App\Middleware\DatabaseOnlineMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class DatabaseOnlineMiddlewareTest
 *
 * Test cases for database online check middleware
 *
 * @package App\Tests\Middleware
 */
class DatabaseOnlineMiddlewareTest extends TestCase
{
    private DatabaseOnlineMiddleware $middleware;
    private Connection & MockObject $connectionMock;
    private CacheManager & MockObject $cacheManagerMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->connectionMock = $this->createMock(Connection::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->cacheManagerMock = $this->createMock(CacheManager::class);

        // create the middleware instance
        $this->middleware = new DatabaseOnlineMiddleware(
            $this->connectionMock,
            $this->errorManagerMock,
            $this->cacheManagerMock
        );
    }

    /**
     * Test if the database connection is successful
     *
     * @return void
     */
    public function testRequestWithSuccessfulDatabaseConnection(): void
    {
        // mock redis connection check
        $this->cacheManagerMock->expects($this->once())->method('isRedisConnected')->willReturn(true);

        // mock the error manager
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // execute the middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test if the redis connection is failed
     *
     * @return void
     */
    public function testRequestWithFailedRedisConnection(): void
    {
        // mock redis connection check
        $this->cacheManagerMock->expects($this->once())->method('isRedisConnected')->willReturn(false);

        // mock the error manager
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'Redis connection error',
            JsonResponse::HTTP_INTERNAL_SERVER_ERROR
        );

        // execute the middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test if the database offline error is handled
     *
     * @return void
     */
    public function testRequestWithFailedDatabaseConnection(): void
    {
        // mock redis connection check
        $this->cacheManagerMock->expects($this->once())->method('isRedisConnected')->willReturn(true);

        // mock the database connection
        $this->connectionMock->expects($this->once())->method('executeQuery')->willThrowException(new Exception('Database connection failed'));

        // expect handle error method call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'Database connection error',
            JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            'Database connection failed'
        );

        // execute the middleware
        $this->middleware->onKernelRequest();
    }
}
