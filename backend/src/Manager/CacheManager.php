<?php

namespace App\Manager;

use Exception;
use Predis\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CacheManager
 *
 * Manager for manipulating with cache storage
 *
 * @package App\Manager
 */
class CacheManager
{
    private Client $redis;
    private ErrorManager $errorManager;

    public function __construct(Client $redis, ErrorManager $errorManager)
    {
        $this->redis = $redis;
        $this->errorManager = $errorManager;
    }

    /**
     * Check if redis connection is ok
     *
     * @return bool True if redis connection is ok, false otherwise
     */
    public function isRedisConnected(): bool
    {
        try {
            $status = $this->redis->ping();
        } catch (Exception) {
            return false;
        }

        // check if redis connection is ok
        if ($status == 'PONG') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Store value in cache storage
     *
     * @param string $key The cache key
     * @param string $value The cache value
     * @param int $expirationTTL The cache expiration time in seconds
     *
     * @return void
     */
    public function saveCacheValue(string $key, string $value, int $expirationTTL = 60): void
    {
        try {
            $this->redis->set($key, $value, 'EX', $expirationTTL);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to save cache value',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }

    /**
     * Check if key exists in cache storage
     *
     * @param string $key The cache key
     *
     * @return bool True if cache value exists, false otherwise
     */
    public function checkIsCacheValueExists(string $key): bool
    {
        try {
            $status = $this->redis->exists($key);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to check cache value exists',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }

        // check if cache value exists
        if ($status == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get cached value from storage
     *
     * @param string $key The cache key
     *
     * @return string|null The cache value if exists, null otherwise
     */
    public function getCacheValue(string $key): ?string
    {
        try {
            return $this->redis->get($key);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to get cache value',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }

    /**
     * Delete cached item from storage
     *
     * @param string $key The cache key
     *
     * @return void
     */
    public function deleteCacheValue(string $key): void
    {
        try {
            $this->redis->del($key);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to delete cache value',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }
}
