<?php

namespace App\Tests\Util;

use App\Util\ChatUtil;
use PHPUnit\Framework\TestCase;

/**
 * Class ChatUtilTest
 *
 * Test cases for chat util
 *
 * @package App\Tests\Util
 */
class ChatUtilTest extends TestCase
{
    private ChatUtil $chatUtil;

    protected function setUp(): void
    {
        $this->chatUtil = new ChatUtil();
    }

    /**
     * Test that chat ID is deterministic and symmetric
     *
     * @return void
     */
    public function testGenerateChatId(): void
    {
        $userA = 1;
        $userB = 5;

        $chatId1 = $this->chatUtil->generateChatId($userA, $userB);
        $chatId2 = $this->chatUtil->generateChatId($userB, $userA);

        // id should be the same regardless of order
        $this->assertEquals($chatId1, $chatId2, 'Chat ID should be symmetric');

        // id should be a string
        $this->assertIsString($chatId1, 'Chat ID should be a string');

        // test uniqueness with different users
        $chatId3 = $this->chatUtil->generateChatId($userA, 6);
        $this->assertNotEquals($chatId1, $chatId3, 'Chat ID should be unique for different user pairs');

        // check hash length (SHA256 = 64 hex chars)
        $this->assertEquals(64, strlen($chatId1), 'Chat ID hash should have length 64');
    }
}
