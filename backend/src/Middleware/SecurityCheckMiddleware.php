<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityCheckMiddleware
 *
 * Middleware for checking security rules
 *
 * @package App\Middleware
 */
class SecurityCheckMiddleware
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Handle security rules check
     *
     * @return void
     */
    public function onKernelRequest(): void
    {
        // check if SSL only enabled
        if ($this->appUtil->isSSLOnly() && !$this->appUtil->isSsl()) {
            $this->errorManager->handleError(
                message: 'SSL is required to access this site',
                code: Response::HTTP_UPGRADE_REQUIRED
            );
        }

        // check if visitor ip is allowed
        $alloedIpAddresses = $this->appUtil->getEnvValue('ALLOWED_IP_ADDRESSES');
        if ($alloedIpAddresses != '%') {
            // split ip addresses to array
            $alloedIpAddresses = explode(',', $alloedIpAddresses);

            // check if visitor ip is allowed
            if (!in_array($this->visitorInfoUtil->getIP(), $alloedIpAddresses)) {
                $this->errorManager->handleError(
                    message: 'Your ip address is not allowed to access this system',
                    code: Response::HTTP_FORBIDDEN
                );
            }
        }
    }
}
