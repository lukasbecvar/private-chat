<?php

namespace App\Event\Subscriber;

use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

/**
 * Class LoginEventSubscriber
 *
 * Login event subscriber for update user data on login and log event to database
 *
 * @package App\Event\Subscriber
 */
class LoginEventSubscriber implements EventSubscriberInterface
{
    private LogManager $logManager;
    private UserManager $userManager;
    private ErrorManager $errorManager;
    private RequestStack $requestStack;

    public function __construct(
        LogManager $logManager,
        UserManager $userManager,
        ErrorManager $errorManager,
        RequestStack $requestStack
    ) {
        $this->logManager = $logManager;
        $this->userManager = $userManager;
        $this->errorManager = $errorManager;
        $this->requestStack = $requestStack;
    }

    /**
     * Return array of event names subscribed by this event
     *
     * @return array<string> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => 'onSecurityAuthenticationSuccess',
        ];
    }

    /**
     * Method called when the AuthenticationSuccessEvent event is dispatched
     *
     * @param AuthenticationSuccessEvent $event The event object
     *
     * @return void
     */
    public function onSecurityAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        // check if request is valid
        if ($request === null) {
            $this->errorManager->handleError(
                message: 'Invalid request',
                code: JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // get path info
        $pathInfo = $request->getPathInfo();

        // check if request is login
        if ($pathInfo == '/api/auth/login') {
            /** @var \App\Entity\User $user */
            $user = $event->getAuthenticationToken()->getUser();
            if ($user === null) {
                $this->errorManager->handleError(
                    message: 'Invalid user',
                    code: JsonResponse::HTTP_BAD_REQUEST
                );
            }

            // get user status
            $status = $user->getStatus();

            // check if user status is active
            if ($status != 'active') {
                $this->errorManager->handleError(
                    message: 'Account is not active, account status is: ' . $status,
                    code: JsonResponse::HTTP_FORBIDDEN
                );
            }

            // get user identifier
            $identifier = $user->getUserIdentifier();

            // update user data
            $this->userManager->updateUserDataOnLogin($identifier);

            // log user auth
            $this->logManager->saveLog(
                name: 'authentication',
                message: 'User: ' . $identifier . ' successfully authenticated',
                level: LogManager::LEVEL_INFO
            );
        }
    }
}
