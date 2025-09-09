<?php

namespace App\Tests\Util;

use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;

/**
 * Class SecurityUtilTest
 *
 * Test cases for security util
 *
 * @package App\Tests\Util
 */
class SecurityUtilTest extends TestCase
{
    private SecurityUtil $securityUtil;
    protected function setUp(): void
    {
        // create the security util instance
        $this->securityUtil = new SecurityUtil();
    }

    /**
     * Test XSS escaping
     *
     * @return void
     */
    public function testEscapeXss(): void
    {
        $input = '<script>alert("XSS");</script>';
        $expectedOutput = '&lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;';

        // call tested method
        $result = $this->securityUtil->escapeString($input);

        // assert result
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test security escaping without XSS
     *
     * @return void
     */
    public function testEscapeNonXss(): void
    {
        $input = 'Hello, World!';
        $expectedOutput = 'Hello, World!';

        // call tested method
        $result = $this->securityUtil->escapeString($input);

        // assert result
        $this->assertEquals($expectedOutput, $result);
    }
}
