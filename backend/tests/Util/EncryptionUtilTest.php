<?php

namespace App\Tests\Util;

use RuntimeException;
use App\Util\AppUtil;
use App\Util\EncryptionUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class EncryptionUtilTest
 *
 * Test cases for encryption util
 *
 * @package App\Tests\Util
 */
class EncryptionUtilTest extends TestCase
{
    private EncryptionUtil $encryptionUtil;
    private MockObject & AppUtil $appUtilMock;
    private MockObject & ErrorManager $errorManagerMock;

    protected function setUp(): void
    {
        // mock app util
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->appUtilMock->method('getEncryptionPassword')->willReturn('testpassword');

        // mock error manager
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->errorManagerMock->method('handleError')->willReturnCallback(function ($msg, $code, $title) {
            throw new RuntimeException($msg);
        });

        // initialize encryption util instance
        $this->encryptionUtil = new EncryptionUtil($this->appUtilMock, $this->errorManagerMock);
    }

    /**
     * Test encrypt and decrypt string
     *
     * @return void
     */
    public function testEncryptAndDecryptString(): void
    {
        // testing string
        $plaintext = "Hello World!";

        // encrypt
        $encrypted = $this->encryptionUtil->encryptString($plaintext);
        $this->assertIsString($encrypted);

        // decrypt
        $decrypted = $this->encryptionUtil->decryptString($encrypted);
        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * Test encrypt invalid iterations triggers error
     *
     * @return void
     */
    public function testEncryptInvalidIterationsTriggersError(): void
    {
        // expect exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid number of PBKDF2 iterations');

        // call tested method
        $this->encryptionUtil->encryptString('data', 0);
    }

    /**
     * Test decrypt invalid base64 triggers error
     *
     * @return void
     */
    public function testDecryptInvalidBase64TriggersError(): void
    {
        // expect exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid base64 input');

        // call tested method
        $this->encryptionUtil->decryptString('invalid_base64!');
    }

    /**
     * Test decrypt invalid json triggers error
     *
     * @return void
     */
    public function testDecryptInvalidJsonTriggersError(): void
    {
        // invalid json
        $invalidJson = base64_encode('not a json');

        // expect exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON input');

        // call tested method
        $this->encryptionUtil->decryptString($invalidJson);
    }
}
