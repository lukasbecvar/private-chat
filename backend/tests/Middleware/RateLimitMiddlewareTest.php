<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Manager\CacheManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\RateLimitMiddleware;
use Symfony\Bundle\SecurityBundle\Security;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class RateLimitMiddlewareTest
 *
 * Test cases for rate limit middleware
 *
 * @package App\Tests\Middleware
 */
class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitMiddleware $middleware;
    private ControllerEvent $controllerEvent;
    private AppUtil & MockObject $appUtilMock;
    private Security & MockObject $securityMock;
    private CacheManager & MockObject $cacheManagerMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->securityMock = $this->createMock(Security::class);
        $this->cacheManagerMock = $this->createMock(CacheManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // instantiate tested middleware
        $this->middleware = new RateLimitMiddleware(
            $this->appUtilMock,
            $this->securityMock,
            $this->cacheManagerMock,
            $this->errorManagerMock
        );

        // create testing event
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = fn() => 'dummy';
        $this->controllerEvent = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    /**
     * Test request when rate limit is disabled
     *
     * @return void
     */
    public function testRequestWhenRateLimitDisabled(): void
    {
        // simulate rate limit disabled
        $this->appUtilMock->method('getEnvValue')->with('RATE_LIMIT_ENABLED')->willReturn('false');

        // expect cache manager to not be called
        $this->cacheManagerMock->expects($this->never())->method('getCacheValue');

        // call tested middleware
        $this->middleware->onKernelController($this->controllerEvent);
    }

    /**
     * Test request when current value is null
     *
     * @return void
     */
    public function testRequestWhenCurrentValueIsNull(): void
    {
        // simulate env configuration
        $this->appUtilMock->method('getEnvValue')->willReturnMap([
            ['RATE_LIMIT_ENABLED', 'true'],
            ['RATE_LIMIT_INTERVAL', '60'],
            ['RATE_LIMIT_LIMIT', '100']
        ]);

        // simulate cache manager to return null
        $this->cacheManagerMock->method('getCacheValue')->willReturn(null);

        // expect call save current value to cache
        $this->cacheManagerMock->expects($this->once())->method('saveCacheValue')->with($this->stringContains('rate_limit:'), '1', 60);

        // call tested middleware
        $this->middleware->onKernelController($this->controllerEvent);
    }

    /**
     * Test request when current value is less than limit
     *
     * @return void
     */
    public function testSubsequentRequestWithinLimit(): void
    {
        // simulate env configuration
        $this->appUtilMock->method('getEnvValue')->willReturnMap([
            ['RATE_LIMIT_ENABLED', 'true'],
            ['RATE_LIMIT_INTERVAL', '60'],
            ['RATE_LIMIT_LIMIT', '100']
        ]);

        // simulate cache manager to return 50
        $this->cacheManagerMock->method('getCacheValue')->willReturn('50');

        // simulate update current rate limit to 51
        $this->cacheManagerMock->expects($this->once())->method('saveCacheValue')->with($this->stringContains('rate_limit:'), '51', 60);

        // call tested middleware
        $this->middleware->onKernelController($this->controllerEvent);
    }

    /**
     * Test request when current value is greater than limit
     *
     * @return void
     */
    public function testRequestExceedingLimit(): void
    {
        // simulate env configuration
        $this->appUtilMock->method('getEnvValue')->willReturnMap([
            ['RATE_LIMIT_ENABLED', 'true'],
            ['RATE_LIMIT_INTERVAL', '60'],
            ['RATE_LIMIT_LIMIT', '100']
        ]);

        // simulate cache manager to return 100
        $this->cacheManagerMock->method('getCacheValue')->willReturn('100');

        // expect error manager to be called
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // call tested middleware
        $this->middleware->onKernelController($this->controllerEvent);
    }
}
