<?php

namespace App\Tests\Manager;

use App\Entity\User;
use App\Util\ChatUtil;
use App\Util\EncryptionUtil;
use App\Manager\ChatManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Entity\PrivateChatMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use App\Repository\PrivateChatMessageRepository;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    private ErrorManager & MockObject $errorManagerMock;
    private ValidatorInterface & MockObject $validatorMock;
    private EncryptionUtil & MockObject $encryptionUtilMock;
    private UserRepository & MockObject $userRepositoryMock;
    private EntityManagerInterface & MockObject $entityManagerMock;
    private PrivateChatMessageRepository & MockObject $privateChatMessageRepositoryMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->chatUtilMock = $this->createMock(ChatUtil::class);
        $this->userManagerMock = $this->createMock(UserManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->encryptionUtilMock = $this->createMock(EncryptionUtil::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->privateChatMessageRepositoryMock = $this->createMock(PrivateChatMessageRepository::class);


        // create chat manager instance
        $this->chatManager = new ChatManager(
            $this->chatUtilMock,
            $this->userManagerMock,
            $this->errorManagerMock,
            $this->validatorMock,
            $this->encryptionUtilMock,
            $this->userRepositoryMock,
            $this->entityManagerMock,
            $this->privateChatMessageRepositoryMock
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
        $this->assertArrayHasKey('last-message', $result[0]);
        $this->assertArrayHasKey('sender-name', $result[0]['last-message']);
        $this->assertArrayHasKey('message', $result[0]['last-message']);
    }

    /**
     * Test check if chat exists
     *
     * @return void
     */
    public function testCheckIfChatExists(): void
    {
        // expect call method from repository
        $this->privateChatMessageRepositoryMock->expects($this->once())->method('getMessageCountByChatId')->willReturn(10);

        // call tested method
        $result = $this->chatManager->checkIfChatExists('chat_1_2');

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test get chat history count
     *
     * @return void
     */
    public function testGetChatHistoryCount(): void
    {
        // expect call method from repository
        $this->privateChatMessageRepositoryMock->expects($this->once())->method('getMessageCountByChatId')->willReturn(10);

        // call tested method
        $result = $this->chatManager->getChatHistoryCount('chat_1_2');

        // assert result
        $this->assertIsInt($result);
    }

    /**
     * Test user has access to chat
     *
     * @return void
     */
    public function testUserHasAccessToChat(): void
    {
        // expect call method from repository
        $this->privateChatMessageRepositoryMock->expects($this->once())->method('userHasAccessToChat')->willReturn(true);

        // call tested method
        $result = $this->chatManager->userHasAccessToChat('chat_1_2', 1);

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test get chat history
     *
     * @return void
     */
    public function testGetChatHistory(): void
    {
        // expect call method from repository
        $this->privateChatMessageRepositoryMock->expects($this->once())->method('getChatHistoryByChatId');

        // call tested method
        $result = $this->chatManager->getChatHistory('chat_1_2');

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test get last chat message
     *
     * @return void
     */
    public function testGetLastChatMessage(): void
    {
        // expect call method from repository
        $this->privateChatMessageRepositoryMock->expects($this->once())->method('getLastMessageByChatId')->willReturn($this->createMock(PrivateChatMessage::class));

        // call tested method
        $result = $this->chatManager->getLastChatMessage('chat_1_2');

        // assert result
        $this->assertInstanceOf(PrivateChatMessage::class, $result);
    }

    /**
     * Test save message to chat
     *
     * @return void
     */
    public function testSaveMessageToChat(): void
    {
        // expect call chat id generation method
        $this->chatUtilMock->expects($this->once())->method('generateChatId')->willReturn('chat_1_2');

        // expect call user entity method
        $this->userManagerMock->expects($this->exactly(2))->method('getUserEntityById')->willReturn($this->createMock(User::class));

        // expect call message entity method
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->chatManager->saveMessageToChat(1, 2, 'message');
    }

    /**
     * Test re-encrypt chat messages
     *
     * @return void
     */
    public function testReEncryptChatMessages(): void
    {
        // prepare fake messages
        $message1 = $this->createMock(PrivateChatMessage::class);
        $message1->method('getId')->willReturn(101);
        $message1->method('getMessage')->willReturn('encrypted_old_1');
        $message1->expects($this->once())->method('setMessage')->with('encrypted_new_1');
        $message2 = $this->createMock(PrivateChatMessage::class);
        $message2->method('getId')->willReturn(102);
        $message2->method('getMessage')->willReturn('encrypted_old_2');
        $message2->expects($this->once())->method('setMessage')->with('encrypted_new_2');

        // repository returns fake messages
        $this->privateChatMessageRepositoryMock->method('findAll')->willReturn([$message1, $message2]);

        // mock decryption method
        $this->encryptionUtilMock->method('decryptAes')->willReturnCallback(fn($encryptedData) => match ($encryptedData) {
            'encrypted_old_1' => 'decrypted_1',
            'encrypted_old_2' => 'decrypted_2',
            default => null,
        });

        // mock encryption method
        $this->encryptionUtilMock->method('encryptAes')->willReturnCallback(fn($plainText, $key) => match ($plainText) {
            'decrypted_1' => 'encrypted_new_1',
            'decrypted_2' => 'encrypted_new_2',
            default => null,
        });

        // expect entity manager calls
        $this->entityManagerMock->expects($this->exactly(2))->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // mock console output
        $ioMock = $this->createMock(SymfonyStyle::class);
        $ioMock->method('writeln');

        // call tested method
        $this->chatManager->reEncryptChatMessages('old_pass', 'new_pass', $ioMock);
    }
}
