<?php

namespace App\Tests\Util;

use App\Util\SecurityUtil;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class VisitorInfoUtilTest
 *
 * Test cases for visitor info util
 *
 * @package App\Tests\Util
 */
class VisitorInfoUtilTest extends TestCase
{
    private SecurityUtil & MockObject $securityUtilMock;
    private VisitorInfoUtil $visitorInfoUtil;

    protected function setUp(): void
    {
        // mock dependencies
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // mock escape string behavior
        $this->securityUtilMock->method('escapeString')->willReturnCallback(function ($string) {
            return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5);
        });

        // create visitor info util instance
        $this->visitorInfoUtil = new VisitorInfoUtil($this->securityUtilMock);
    }

    /**
     * Test get visitor ip address
     *
     * @return void
     */
    public function testGetIpAddress(): void
    {
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.2';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.3';

        // test test ip from HTTP
        $ip = $this->visitorInfoUtil->getIP();
        $this->assertEquals('192.168.1.1', $ip);

        // test get ip from HTTP_X_FORWARDED_FOR
        $_SERVER['HTTP_CLIENT_IP'] = '';
        $ip = $this->visitorInfoUtil->getIP();
        $this->assertEquals('192.168.1.2', $ip);

        // test get ip from REMOTE_ADDR
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';
        $ip = $this->visitorInfoUtil->getIP();
        $this->assertEquals('192.168.1.3', $ip);
    }

    /**
     * Test get user agent
     *
     * @return void
     */
    public function testGetUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

        // call tested method
        $userAgent = $this->visitorInfoUtil->getUserAgent();

        // assert result
        $this->assertEquals('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', $userAgent);
    }

    /**
     * Test get short browser name
     *
     * @return void
     */
    public function testGetBrowserShortify(): void
    {
        $userAgentChrome = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $userAgentFirefox = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Gecko/20100101 Firefox/91.0';

        // test for chrome
        $browser = $this->visitorInfoUtil->getBrowserShortify($userAgentChrome);
        $this->assertEquals('Chrome', $browser);

        // test for firefox
        $browser = $this->visitorInfoUtil->getBrowserShortify($userAgentFirefox);
        $this->assertEquals('Firefox', $browser);
    }

    /**
     * Test get operating system
     *
     * @return void
     */
    public function testGetOs(): void
    {
        $userAgentWindows = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $userAgentLinux = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

        // test get windows
        $os = $this->visitorInfoUtil->getOs($userAgentWindows);
        $this->assertEquals('Windows 10', $os);

        // test get linux
        $os = $this->visitorInfoUtil->getOs($userAgentLinux);
        $this->assertEquals('Ubuntu', $os);
    }
}
