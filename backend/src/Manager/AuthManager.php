<?php

namespace App\Manager;

use App\Util\AppUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AuthManager
 *
 * Manager for user authentication and authorization system
 *
 * @package App\Manager
 */
class AuthManager
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private CacheManager $cacheManager;
    private ErrorManager $errorManager;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        CacheManager $cacheManager,
        ErrorManager $errorManager,
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->cacheManager = $cacheManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Get auth token from request or cookie
     *
     * @param Request $request The request object
     *
     * @return string|null The auth token or null if not set
     */
    public function getAuthTokenFromRequest(Request $request): ?string
    {
        // try to get token from authorization header
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader) {
            if (str_starts_with($authHeader, 'Bearer ')) {
                return substr($authHeader, 7);
            }
            return $authHeader;
        }

        // try to get token from cookie
        $cookieToken = $request->cookies->get('BEARER');
        if ($cookieToken) {
            return $cookieToken;
        }

        return null;
    }

    /**
     * Logout user from system (blacklist auth token)
     *
     * @param string $authToken The auth token
     * @param Security $security The security object
     *
     * @return void
     */
    public function logout(string $authToken, Security $security)
    {
        // get user
        $user = $security->getUser();

        // check if user is set
        if ($user == null) {
            $this->errorManager->handleError(
                message: 'Error to get user by auth token',
                code: JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        // get user identifier
        $userIdentifier = $user->getUserIdentifier();

        // invalidate token
        $this->blacklistToken($authToken);

        // log event logout
        $this->logManager->saveLog(
            name: 'authenticator',
            message: 'User: ' . $userIdentifier . ' logged out',
            level: LogManager::LEVEL_INFO
        );
    }

    /**
     * Blacklist auth token
     *
     * @param string $authToken The auth token
     *
     * @return void
     */
    public function blacklistToken(string $authToken): void
    {
        $this->cacheManager->saveCacheValue(
            key: 'blacklisted-token:' . $authToken,
            value: 'blacklisted',
            expirationTTL: (int) $this->appUtil->getEnvValue('JWT_TOKEN_TTL')
        );
    }

    /**
     * Check if auth token is blacklisted
     *
     * @param string $authToken The auth token
     *
     * @return bool True if token is blacklisted, false otherwise
     */
    public function isTokenBlacklisted(string $authToken): bool
    {
        // cgeck if token is blacklisted
        if (!$this->cacheManager->checkIsCacheValueExists('blacklisted-token:' . $authToken)) {
            return false;
        }

        // get blacklisted token from cache
        $value = $this->cacheManager->getCacheValue('blacklisted-token:' . $authToken);

        // return true if token is blacklisted
        return $value === 'blacklisted';
    }
}
