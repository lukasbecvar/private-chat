<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\MaintenanceMiddleware;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class MaintenanceMiddlewareTest
 *
 * Test cases for maintenance mode middleware
 *
 * @package App\Tests\Middleware
 */
class MaintenanceMiddlewareTest extends TestCase
{
    private MaintenanceMiddleware $middleware;
    private AppUtil & MockObject $appUtilMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create the middleware instance
        $this->middleware = new MaintenanceMiddleware(
            $this->appUtilMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test if the maintenance mode is enabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeEnabled(): void
    {
        // mock the app util
        $this->appUtilMock->expects($this->once())->method('isMaintenance')->willReturn(true);

        // expect handle error method call
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // execute the middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test if the maintenance mode is disabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeDisabled(): void
    {
        // mock the app util
        $this->appUtilMock->expects($this->once())->method('isMaintenance')->willReturn(false);

        // mock the error manager
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // execute the middleware
        $this->middleware->onKernelRequest();
    }
}
