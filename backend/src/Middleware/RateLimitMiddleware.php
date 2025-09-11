<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Manager\CacheManager;
use App\Manager\ErrorManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

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
    private Security $security;
    private CacheManager $cacheManager;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, Security $security, CacheManager $cacheManager, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->security = $security;
        $this->cacheManager = $cacheManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle rate limiting
     *
     * @param ControllerEvent $event The controller event object
     *
     * @return void
     */
    public function onKernelController(ControllerEvent $event): void
    {
        // check if rate limit is enabled
        if ($this->appUtil->getEnvValue('RATE_LIMIT_ENABLED') == 'false') {
            return;
        }

        // disable rate limiting for authenticated users
        if ($this->security->getUser() !== null) {
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
