<?php

namespace App\Middleware;

use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class UserStatusCheckMiddleware
 *
 * Middleware for check if user is active
 *
 * @package App\Middleware
 */
class UserStatusCheckMiddleware
{
    private Security $security;
    private UserManager $userManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;

    public function __construct(
        Security $security,
        UserManager $userManager,
        AuthManager $authManager,
        ErrorManager $errorManager
    ) {
        $this->security = $security;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Check if user is active
     *
     * @param RequestEvent $event The request event object
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // get auth token from request
        $token = $this->authManager->getAuthTokenFromRequest($request);

        // check if token set in request
        if (!empty($token)) {
            /** @var \App\Entity\User $user */
            $user = $this->security->getUser();

            // get user id
            $userId = $user->getId();

            // check if user id found
            if ($userId == null) {
                $this->errorManager->handleError(
                    message: 'User getting error',
                    code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // get user status
            $userStatus = $this->userManager->getUserStatus($userId);

            // check if user status is active
            if ($userStatus !== 'active') {
                $this->errorManager->handleError(
                    message: 'Your account is not active, your current status is: ' . $userStatus,
                    code: JsonResponse::HTTP_UNAUTHORIZED
                );
            }
        }
    }
}
