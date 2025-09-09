<?php

namespace App\Tests\Middleware;

use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use App\Middleware\AuthTokenValidateMiddleware;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AuthTokenValidateMiddlewareTest
 *
 * Test cases for auth token validate middleware
 *
 * @package App\Tests\Middleware
 */
class AuthTokenValidateMiddlewareTest extends TestCase
{
    private AuthManager & MockObject $authManager;
    private ErrorManager & MockObject $errorManager;
    private AuthTokenValidateMiddleware $middleware;

    protected function setUp(): void
    {
        // mock dependencies
        $this->authManager = $this->createMock(AuthManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);

        // init middleware instance
        $this->middleware = new AuthTokenValidateMiddleware($this->authManager, $this->errorManager);
    }

    /**
     * Test request when token is blacklisted
     *
     * @return void
     */
    public function testRequestWhenTokenIsBlacklisted(): void
    {
        $request = new Request([], [], [], [], [], [], '');
        $token = 'testing_token';

        // mock auth token get
        $this->authManager->expects($this->once())->method('getAuthTokenFromRequest')->with($request)->willReturn($token);

        // mock token blacklist check
        $this->authManager->expects($this->once())->method('isTokenBlacklisted')->with($token)->willReturn(true);

        // expect error handler call
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->equalTo('Invalid JWT token'),
            $this->equalTo(JsonResponse::HTTP_UNAUTHORIZED)
        );

        // mock request event
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when token is not blacklisted
     *
     * @return void
     */
    public function testRequestWhenTokenIsNotBlacklisted(): void
    {
        $request = new Request([], [], [], [], [], [], '');
        $token = 'testing_token';

        // mock auth token get
        $this->authManager->expects($this->once())->method('getAuthTokenFromRequest')->with($request)->willReturn($token);

        // mock token blacklist check
        $this->authManager->expects($this->once())->method('isTokenBlacklisted')->with($token)->willReturn(false);

        // expect error handler not be called
        $this->errorManager->expects($this->never())->method('handleError');

        // mock request event
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }
}
