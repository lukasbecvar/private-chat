<?php

namespace App\Tests\Util;

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
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // initialize encryption util instance
        $this->encryptionUtil = new EncryptionUtil($this->appUtilMock, $this->errorManagerMock);
    }

    /**
     * Test encryption/dencryption with AES
     *
     * @return void
     */
    public function testEncryptionAndDecryption(): void
    {
        // encrypt string to aes
        $encryptedData = $this->encryptionUtil->encryptAes('test value');

        // decrypt string from aes
        $decryptedData = $this->encryptionUtil->decryptAes($encryptedData);

        // assert result
        $this->assertSame('test value', $decryptedData);
    }
}
