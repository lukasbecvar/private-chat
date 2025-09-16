<?php

namespace App\Tests\Manager;

use App\Entity\User;
use App\Util\ChatUtil;
use App\Manager\ChatManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ChatManagerTest
 *
 * Test cases for chat manager
 *
 * @package App\Tests\Manager
 */
class ChatManagerTest extends TestCase
{
    private ChatManager $chatManager;
    private ChatUtil & MockObject $chatUtilMock;
    private UserManager & MockObject $userManagerMock;
    private UserRepository & MockObject $userRepositoryMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->chatUtilMock = $this->createMock(ChatUtil::class);
        $this->userManagerMock = $this->createMock(UserManager::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);

        // create chat manager instance
        $this->chatManager = new ChatManager(
            $this->chatUtilMock,
            $this->userManagerMock,
            $this->userRepositoryMock
        );
    }

    /**
     * Test get available chats list
     *
     * @return void
     */
    public function testGetAvailableChatsList(): void
    {
        // mock testing users
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user1->method('getUsername')->willReturn('Alice');
        $user1->method('getProfilePic')->willReturn('alice.png');
        $user1->method('isActive')->willReturn(true);
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);
        $user2->method('getUsername')->willReturn('Bob');
        $user2->method('getProfilePic')->willReturn('bob.png');
        $user2->method('isActive')->willReturn(true);
        $user3 = $this->createMock(User::class);
        $user3->method('getId')->willReturn(3);
        $user3->method('getUsername')->willReturn('InactiveUser');
        $user3->method('getProfilePic')->willReturn('inactive.png');
        $user3->method('isActive')->willReturn(false);

        // mock user repository
        $this->userRepositoryMock->method('findAll')
            ->willReturn([$user1, $user2, $user3]);

        // mock generate chat id
        $this->chatUtilMock->method('generateChatId')
            ->willReturnCallback(fn($idA, $idB) => "chat_{$idA}_{$idB}");

        // call tested method
        $result = $this->chatManager->getAvailableChatsList(1);

        // assert result
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['user-id']);
        $this->assertEquals('chat_1_2', $result[0]['chat-id']);
        $this->assertIsBool($result[0]['is-admin']);
        $this->assertEquals('Bob', $result[0]['username']);
        $this->assertEquals('bob.png', $result[0]['profile-picture']);
    }
}
