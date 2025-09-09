<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Manager\CacheManager;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class RateLimitMiddleware
 *
 * Middleware for request rate limiting
 *
 * @package App\Middleware
 */
class RateLimitMiddleware
{
    private AppUtil $appUtil;
    private CacheManager $cacheManager;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, CacheManager $cacheManager, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->cacheManager = $cacheManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle rate limiting
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // check if rate limit is enabled
        if ($this->appUtil->getEnvValue('RATE_LIMIT_ENABLED') == 'false') {
            return;
        }

        // get request object
        $request = $event->getRequest();

        // build key for cache
        $key = 'rate_limit:' . $request->getClientIp();

        // get current value from cache
        $current = $this->cacheManager->getCacheValue($key);

        if ($current === null) {
            // set current value to 1 and save to cache (for first request)
            $this->cacheManager->saveCacheValue($key, '1', (int) $this->appUtil->getEnvValue('RATE_LIMIT_INTERVAL'));
        } elseif ((int)$current >= (int) $this->appUtil->getEnvValue('RATE_LIMIT_LIMIT')) {
            $this->errorManager->handleError(
                message: 'To many requests!',
                code: Response::HTTP_TOO_MANY_REQUESTS
            );
        } else {
            // increment current value and save to cache
            $this->cacheManager->saveCacheValue($key, (string)((int)$current + 1), (int) $this->appUtil->getEnvValue('RATE_LIMIT_INTERVAL'));
        }
    }
}
