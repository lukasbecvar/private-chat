<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\CacheManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AuthManagerTest
 *
 * Test cases for auth manager
 *
 * @package App\Tests\Manager
 */
class AuthManagerTest extends TestCase
{
    private AuthManager $authManager;
    private AppUtil & MockObject $appUtil;
    private Security & MockObject $security;
    private LogManager & MockObject $logManager;
    private CacheManager & MockObject $cacheManager;
    private ErrorManager & MockObject $errorManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->security = $this->createMock(Security::class);
        $this->logManager = $this->createMock(LogManager::class);
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);

        // init auth manager instance
        $this->authManager = new AuthManager(
            $this->appUtil,
            $this->logManager,
            $this->cacheManager,
            $this->errorManager
        );
    }

    /**
     * Test get auth token from request
     *
     * @return void
     */
    public function testGetAuthTokenFromRequest(): void
    {
        // creating mock request and setting authorization header
        $request = new Request([], [], [], [], [], ['HTTP_Authorization' => 'Bearer mock_token']);

        // call tested method
        $token = $this->authManager->getAuthTokenFromRequest($request);

        // assert result
        $this->assertEquals('Bearer mock_token', $token);
    }

    /**
     * Test blacklist token
     *
     * @return void
     */
    public function testBlacklistToken(): void
    {
        // mock get env value
        $this->appUtil->expects($this->once())->method('getEnvValue')->with('JWT_TOKEN_TTL')
            ->willReturn('3600');

        // expect save cache value method to be called
        $this->cacheManager->expects($this->once())->method('saveCacheValue')->with(
            $this->equalTo('blacklisted-token:mocked_token'),
            $this->equalTo('blacklisted'),
            $this->equalTo(3600)
        );

        // call tested method
        $this->authManager->blacklistToken('mocked_token');
    }

    /**
     * Test check is token blacklisted when not blacklisted
     *
     * @return void
     */
    public function testIsTokenBlacklistedWhenNotBlacklisted(): void
    {
        // mock check is cache value exists method
        $this->cacheManager->expects($this->once())->method('checkIsCacheValueExists')->with('blacklisted-token:mocked_token')->willReturn(false);

        // call tested method
        $isBlacklisted = $this->authManager->isTokenBlacklisted('mocked_token');

        // assert result
        $this->assertFalse($isBlacklisted);
    }

    /**
     * Test check is token blacklisted when blacklisted
     *
     * @return void
     */
    public function testIsTokenBlacklistedWhenBlacklisted(): void
    {
        // mock check is cache value exists
        $this->cacheManager->expects($this->once())->method('checkIsCacheValueExists')->with('blacklisted-token:mocked_token')->willReturn(true);

        // expect get cache value method call
        $this->cacheManager->expects($this->once())->method('getCacheValue')->with('blacklisted-token:mocked_token')->willReturn('blacklisted');

        // call tested method
        $isBlacklisted = $this->authManager->isTokenBlacklisted('mocked_token');

        // assert result
        $this->assertTrue($isBlacklisted);
    }

    /**
     * Test user logout
     *
     * @return void
     */
    public function testLogout(): void
    {
        // mock user with identifier
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())->method('getUserIdentifier')->willReturn('mocked_user');
        $this->security->expects($this->once())->method('getUser')->willReturn($user);

        // expect save auto token to blacklist
        $this->cacheManager->expects($this->once())->method('saveCacheValue');

        // expect save log call
        $this->logManager->expects($this->once())->method('saveLog')->with(
            $this->equalTo('authenticator'),
            $this->equalTo('User: mocked_user logged out'),
            $this->equalTo(LogManager::LEVEL_INFO)
        );

        // call tested method
        $this->authManager->logout('mocked_token', $this->security);
    }
}
