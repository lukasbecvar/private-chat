<?php

namespace App\Tests\Manager;

use Predis\Client;
use App\Manager\CacheManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CacheManagerTest
 *
 * Test cases for cache manager
 *
 * @package App\Tests\Manager
 */
class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;
    private Client & MockObject $redis;
    private ErrorManager & MockObject $errorManager;

    public function setUp(): void
    {
        // mock dependencies
        $this->redis = $this->createMock(Client::class);
        $this->errorManager = $this->createMock(ErrorManager::class);

        // init cache manager instance
        $this->cacheManager = new CacheManager($this->redis, $this->errorManager);
    }

    /**
     * Test check is redis connected
     *
     * @return void
     */
    public function testCheckIsRedisConnected(): void
    {
        // expect call check redis connection
        $this->redis->expects($this->once())->method('__call')->with('ping');

        // call tested method
        $this->cacheManager->isRedisConnected();
    }

    /**
     * Test save cache value
     *
     * @return void
     */
    public function testSaveCacheValue(): void
    {
        // expect call set cache value
        $this->redis->expects($this->once())->method('__call')->with('set', ['test_key', 'test_value', 'EX', 60]);

        // expect no error handling
        $this->errorManager->expects($this->never())->method('handleError');

        // call tested method
        $this->cacheManager->saveCacheValue('test_key', 'test_value');
    }

    /**
     * Test save cache value with error handling
     *
     * @return void
     */
    public function testCheckIsCacheValueExists(): void
    {
        // expect call check cache value exists
        $this->redis->expects($this->once())->method('__call')->with('exists', ['test_key']);

        // expect no error handling
        $this->errorManager->expects($this->never())->method('handleError');

        // call tested method
        $this->cacheManager->checkIsCacheValueExists('test_key');
    }

    /**
     * Test check is cache value exists
     *
     * @return void
     */
    public function testGetCacheValue(): void
    {
        // expect call get cache value
        $this->redis->expects($this->once())->method('__call')->with('get', ['test_key']);

        // expect no error handling
        $this->errorManager->expects($this->never())->method('handleError');

        // call tested method
        $this->cacheManager->getCacheValue('test_key');
    }

    /**
     * Test delete cache value
     *
     * @return void
     */
    public function testDeleteCacheValue(): void
    {
        // expect call delete cache value
        $this->redis->expects($this->once())->method('__call')->with('del', ['test_key']);

        // expect no error handling
        $this->errorManager->expects($this->never())->method('handleError');

        // call tested method
        $this->cacheManager->deleteCacheValue('test_key');
    }
}
