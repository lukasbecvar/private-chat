<?php

namespace App\Tests\Event\Subscriber;

use App\Entity\User;
use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use App\Event\Subscriber\LoginEventSubscriber;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class LoginEventSubscriberTest
 *
 * Test cases for login event subscriber
 *
 * @package App\Tests\Event\Subscriber
 */
class LoginEventSubscriberTest extends TestCase
{
    private LogManager & MockObject $logManager;
    private UserManager & MockObject $userManager;
    private ErrorManager & MockObject $errorManager;
    private RequestStack & MockObject $requestStack;
    private LoginEventSubscriber $loginEventSubscriber;

    protected function setUp(): void
    {
        // mock dependencies
        $this->logManager = $this->createMock(LogManager::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        // init login event subscriber
        $this->loginEventSubscriber = new LoginEventSubscriber(
            $this->logManager,
            $this->userManager,
            $this->errorManager,
            $this->requestStack
        );
    }

    /**
     * Test get subscribed events
     *
     * @return void
     */
    public function testGetSubscribedEvents(): void
    {
        $this->assertArrayHasKey(AuthenticationSuccessEvent::class, LoginEventSubscriber::getSubscribedEvents());
    }

    /**
     * Test security authentication success
     *
     * @return void
     */
    public function testSecurityAuthenticationSuccess(): void
    {
        // create testing request
        $request = $this->createMock(Request::class);
        $request->method('getPathInfo')->willReturn('/api/auth/login');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        // create testing user
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn('testuser');
        $user->method('getStatus')->willReturn('active');
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // create testing event
        $event = new AuthenticationSuccessEvent($token);

        // expect update user data call
        $this->userManager->expects($this->once())->method('updateUserDataOnLogin')->with('testuser');

        // expect save log call
        $this->logManager->expects($this->once())->method('saveLog')->with(
            'authentication',
            'User: testuser successfully authenticated',
            LogManager::LEVEL_INFO
        );

        // call tested event subscriber
        $this->loginEventSubscriber->onSecurityAuthenticationSuccess($event);
    }

    /**
     * Test security authentication success with inactive user
     *
     * @return void
     */
    public function testSecurityAuthenticationSuccessWithInactiveUser(): void
    {
        // create testing request
        $request = $this->createMock(Request::class);
        $request->method('getPathInfo')->willReturn('/api/auth/login');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        // create testing user
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn('testuser');
        $user->method('getStatus')->willReturn('inactive');
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // create testing event
        $event = new AuthenticationSuccessEvent($token);

        // expect error manager call
        $this->errorManager->expects($this->once())->method('handleError')->with(
            'Account is not active, account status is: inactive',
            JsonResponse::HTTP_FORBIDDEN
        );

        // call tested event subscriber
        $this->loginEventSubscriber->onSecurityAuthenticationSuccess($event);
    }
}
