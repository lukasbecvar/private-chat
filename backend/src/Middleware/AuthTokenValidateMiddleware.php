<?php

namespace App\Middleware;

use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AuthTokenValidateMiddleware
 *
 * Middleware for validate auth token and check if token is blacklisted
 *
 * @package App\Middleware
 */
class AuthTokenValidateMiddleware
{
    private AuthManager $authManager;
    private ErrorManager $errorManager;

    public function __construct(AuthManager $authManager, ErrorManager $errorManager)
    {
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Validate auth token and check if token is blacklisted
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
            if ($this->authManager->isTokenBlacklisted($token)) {
                $this->errorManager->handleError(
                    message: 'Invalid JWT token',
                    code: JsonResponse::HTTP_UNAUTHORIZED
                );
            }
        }
    }
}
