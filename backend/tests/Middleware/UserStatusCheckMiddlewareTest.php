<?php

namespace App\Tests\Middleware;

use App\Entity\User;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use PHPUnit\Framework\MockObject\MockObject;
use App\Middleware\UserStatusCheckMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class UserStatusCheckMiddlewareTest
 *
 * Test cases for user status check middleware
 *
 * @package App\Tests\Middleware
 */
class UserStatusCheckMiddlewareTest extends TestCase
{
    private Security & MockObject $security;
    private UserManager & MockObject $userManager;
    private AuthManager & MockObject $authManager;
    private ErrorManager & MockObject $errorManager;
    private UserStatusCheckMiddleware $middleware;

    protected function setUp(): void
    {
        // mock dependencies
        $this->security = $this->createMock(Security::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->authManager = $this->createMock(AuthManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);

        // init middleware instance
        $this->middleware = new UserStatusCheckMiddleware(
            $this->security,
            $this->userManager,
            $this->authManager,
            $this->errorManager
        );
    }

    /**
     * Test request when user status is inactive
     *
     * @return void
     */
    public function testRequestWhenUserIsInactive(): void
    {
        $request = new Request([], [], [], [], [], [], '');
        $token = 'testing_token';

        // mock auth token get
        $this->authManager->expects($this->once())->method('getAuthTokenFromRequest')->with($request)->willReturn($token);

        // mock user
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getId')->willReturn(1);
        $this->security->expects($this->once())->method('getUser')->willReturn($user);

        // mock inactive status
        $this->userManager->expects($this->once())->method('getUserStatus')->with(1)->willReturn('inactive');

        // expect error handler call
        $this->errorManager->expects($this->once())->method('handleError')->with(
            'Your account is not active, your current status is: inactive',
            JsonResponse::HTTP_UNAUTHORIZED
        );

        // create testing request
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when user status is active
     *
     * @return void
     */
    public function testRequestWhenUserIsActive(): void
    {
        $request = new Request([], [], [], [], [], [], '');
        $token = 'testing_token';

        // mock auth token get
        $this->authManager->expects($this->once())->method('getAuthTokenFromRequest')->with($request)->willReturn($token);

        // mock user
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getId')->willReturn(1);
        $this->security->expects($this->once())->method('getUser')->willReturn($user);

        // mock active status
        $this->userManager->expects($this->once())->method('getUserStatus')->with(1)->willReturn('active');

        // expect error handler not be called
        $this->errorManager->expects($this->never())->method('handleError');

        // create testing request
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }
}
