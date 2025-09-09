<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use App\Middleware\AccessTokenValidateMiddleware;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AccessTokenValidateMiddlewareTest
 *
 * Test cases for access token validate middleware
 *
 * @package App\Tests\Middleware
 */
class AccessTokenValidateMiddlewareTest extends TestCase
{
    private AppUtil & MockObject $appUtil;
    private ErrorManager & MockObject $errorManager;
    private AccessTokenValidateMiddleware $middleware;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);

        // create middleware instance
        $this->middleware = new AccessTokenValidateMiddleware($this->appUtil, $this->errorManager);
    }

    /**
     * Test request when token is invalid
     *
     * @return void
     */
    public function testRequestWhenTokenIsInvalid(): void
    {
        // testing request
        $request = new Request([], [], [], [], [], [], '{"email": "test@test.test", "password": "test"}');
        $request->setMethod('POST');
        $request->headers->set('X-API-TOKEN', 'invalid_token');
        $request->headers->set('Content-Type', 'application/json');
        $request->server->set('REQUEST_URI', '/api/auth/login');

        // mock api token from .env
        $this->appUtil->expects($this->once())->method('getEnvValue')->with('API_TOKEN')->willReturn('valid_token');

        // expect error handler to be called for invalid token
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->equalTo('Invalid access token.'),
            $this->equalTo(JsonResponse::HTTP_UNAUTHORIZED)
        );

        // mock request event
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when token is valid
     *
     * @return void
     */
    public function testRequestWhenTokenIsValid(): void
    {
        // testing request
        $request = new Request([], [], [], [], [], [], '{"email": "test@test.test", "password": "test"}');
        $request->setMethod('POST');
        $request->headers->set('X-API-TOKEN', 'valid_token');
        $request->headers->set('Content-Type', 'application/json');
        $request->server->set('REQUEST_URI', '/api/auth/login');

        // mock api token from .env
        $this->appUtil->expects($this->once())->method('getEnvValue')->with('API_TOKEN')->willReturn('valid_token');

        // expect error handler NOT to be called for valid token
        $this->errorManager->expects($this->never())->method('handleError');

        // mock request event
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when path is not API (should not validate token)
     *
     * @return void
     */
    public function testRequestWhenPathIsNotApi(): void
    {
        // testing request
        $request = new Request([], [], [], [], [], []);
        $request->setMethod('POST');
        $request->headers->set('X-API-TOKEN', 'valid_token');
        $request->server->set('REQUEST_URI', '/some/other/path');

        // expect no error handler to be called for non API request
        $this->appUtil->expects($this->never())->method('getEnvValue');
        $this->errorManager->expects($this->never())->method('handleError');

        // mock request event
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }
}
