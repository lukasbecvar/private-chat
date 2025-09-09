<?php

namespace App\Manager;

use App\Util\AppUtil;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManager
 *
 * Manager for error handling functionality
 *
 * @package App\Manager
 */
class ErrorManager
{
    private AppUtil $appUtil;

    public function __construct(AppUtil $appUtil)
    {
        $this->appUtil = $appUtil;
    }

    /**
     * Handle error exception
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param string|null $exceptionMessage The exception message
     *
     * @return never Always throws error exception
     */
    public function handleError(string $message, int $code, ?string $exceptionMessage = null): void
    {
        // append exception message to error message for dev mode
        if ($this->appUtil->isDevMode() && $exceptionMessage != null) {
            $message .= ': ' . $exceptionMessage;
        }

        throw new HttpException($code, $message, null, [], $code);
    }
}
