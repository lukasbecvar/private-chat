<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MaintenanceMiddleware
 *
 * Middleware for handle maintenance mode
 *
 * @package App\Middleware
 */
class MaintenanceMiddleware
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle maintenance mode
     *
     * @return void
     */
    public function onKernelRequest(): void
    {
        // check if maintenance mode is enabled
        if ($this->appUtil->isMaintenance()) {
            $this->errorManager->handleError(
                message: 'Application is under maintenance mode',
                code: Response::HTTP_SERVICE_UNAVAILABLE
            );
        }
    }
}
