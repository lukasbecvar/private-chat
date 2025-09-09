<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AccessTokenValidateMiddleware
 *
 * Middleware for validate access token
 *
 * @package App\Middleware
 */
class AccessTokenValidateMiddleware
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Validate access token
     *
     * @param RequestEvent $event The request event object
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // disable validator for api doc endpoint or non api endpoints
        if (!str_starts_with($pathInfo, '/api') || str_starts_with($pathInfo, '/api/doc')) {
            return;
        }

        // get access token from request
        $providedToken = $request->headers->get('X-API-TOKEN');

        // check if access token is valid
        if ($providedToken !== $this->appUtil->getEnvValue('API_TOKEN')) {
            $this->errorManager->handleError(
                message: 'Invalid access token.',
                code: Response::HTTP_UNAUTHORIZED
            );
        }
    }
}
