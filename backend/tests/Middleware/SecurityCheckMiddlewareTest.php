<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Middleware\SecurityCheckMiddleware;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SecurityCheckMiddlewareTest
 *
 * Test cases for security check middleware
 *
 * @package App\Tests\Middleware
 */
class SecurityCheckMiddlewareTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private SecurityCheckMiddleware $middleware;
    private ErrorManager & MockObject $errorManagerMock;
    private VisitorInfoUtil & MockObject $visitorInfoUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);

        // create security check middleware instance
        $this->middleware = new SecurityCheckMiddleware(
            $this->appUtilMock,
            $this->errorManagerMock,
            $this->visitorInfoUtilMock
        );
    }

    /**
     * Test if ssl is enabled and ssl is not detected
     *
     * @return void
     */
    public function testRequestWhenSslEnabledAndSslNotDetected(): void
    {
        // configure mock expectations for this specific test
        $this->appUtilMock->expects($this->once())->method('isSSLOnly')->willReturn(true);
        $this->appUtilMock->expects($this->once())->method('isSsl')->willReturn(false);

        // expect handle error method call
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // execute tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test if ssl is enabled and ssl is detected
     *
     * @return void
     */
    public function testRequestWhenSslEnabledAndSslDetected(): void
    {
        // configure mock expectations for this specific test
        $this->appUtilMock->expects($this->once())->method('isSSLOnly')->willReturn(true);
        $this->appUtilMock->expects($this->once())->method('isSsl')->willReturn(true);

        // expect no errors to be handled
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // execute tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test if the ssl is not enabled
     *
     * @return void
     */
    public function testRequestWhenSslNotEnabled(): void
    {
        // configure mock expectations for this specific test
        $this->appUtilMock->expects($this->once())->method('isSSLOnly')->willReturn(false);

        // expect no errors to be handled
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // execute tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test if the ip address is allowed
     *
     * @return void
     */
    public function testRequestWhenIpAddressIsAllowed(): void
    {
        // mock allowed ip address
        $this->appUtilMock->expects($this->once())->method('getEnvValue')->willReturn('127.0.0.1');

        // mock get ip address
        $this->visitorInfoUtilMock->expects($this->once())->method('getIP')->willReturn('127.0.0.1');

        // expect error handler not to be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // execute
        $this->middleware->onKernelRequest();
    }

    /**
     * Test if the ip address is not allowed
     *
     * @return void
     */
    public function testRequestWhenIpAddressIsNotAllowed(): void
    {
        // mock allowed ip address
        $this->appUtilMock->expects($this->once())->method('getEnvValue')->willReturn('127.0.0.1,192.168.0.1');

        // expect error handler call
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // execute tested middleware
        $this->middleware->onKernelRequest();
    }
}
