<?php

namespace App\Tests\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use App\Entity\PrivateChatMessage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PrivateChatMessageRepositoryTest
 *
 * Test cases for doctrine private chat message repository
 *
 * @package App\Tests\Repository
 */
class PrivateChatMessageRepositoryTest extends KernelTestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @phpstan-ignore-next-line */
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test get message count by chat ID
     *
     * @return void
     */
    public function testGetMessageCountByChatId(): void
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $this->assertGreaterThanOrEqual(2, count($users), 'There should be at least 2 users for message count test');

        $userA = $users[0];
        $userB = $users[1];

        $ids = [(int)$userA->getId(), (int)$userB->getId()];
        sort($ids);
        $chatId = hash('sha256', $ids[0] . '_' . $ids[1]);

        /** @var \App\Repository\PrivateChatMessageRepository $repo */
        $repo = $this->entityManager->getRepository(PrivateChatMessage::class);

        // call tested method
        $count = $repo->getMessageCountByChatId($chatId);

        // assert result
        $this->assertIsInt($count, 'Message count should be an integer');
        $this->assertGreaterThanOrEqual(0, $count, 'Message count should not be negative');
    }

    /**
     * Test user access to chat
     *
     * @return void
     */
    public function testUserHasAccessToChat(): void
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $this->assertGreaterThanOrEqual(2, count($users), 'There should be at least 2 users for access test');

        $userA = $users[0];
        $userB = $users[1];

        $ids = [(int)$userA->getId(), (int)$userB->getId()];
        sort($ids);
        $chatId = hash('sha256', $ids[0] . '_' . $ids[1]);

        /** @var \App\Repository\PrivateChatMessageRepository $repo */
        $repo = $this->entityManager->getRepository(PrivateChatMessage::class);

        // call tested method and assert result
        $this->assertTrue($repo->userHasAccessToChat($chatId, (int)$userA->getId()), 'User A should have access');
        $this->assertTrue($repo->userHasAccessToChat($chatId, (int)$userB->getId()), 'User B should have access');
        $this->assertFalse($repo->userHasAccessToChat($chatId, 999999), 'Random user should not have access');
    }

    /**
     * Test get chat history by chat ID
     *
     * @return void
     */
    public function testGetChatHistoryByChatId(): void
    {
        // get two users from DB
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $this->assertGreaterThanOrEqual(2, count($users), 'There should be at least 2 users for chat test');

        $userA = $users[0];
        $userB = $users[1];

        // calculate chatId using the same deterministic method
        $ids = [$userA->getId(), $userB->getId()];
        sort($ids);
        $chatId = hash('sha256', $ids[0] . '_' . $ids[1]);

        /** @var \App\Repository\PrivateChatMessageRepository $repo */
        $repo = $this->entityManager->getRepository(PrivateChatMessage::class);

        // get chat history
        $messages = $repo->getChatHistoryByChatId($chatId);

        // assert that we get an array
        $this->assertIsArray($messages, 'Chat history should be an array');
        $this->assertNotEmpty($messages, 'Chat history should not be empty');

        // assert each message has correct chatId and sender/receiver
        foreach ($messages as $message) {
            // assert chat reciver and sender ids are correct
            $this->assertEquals($chatId, $message->getChatId());
            $this->assertContains($message->getSender()->getId(), [$userA->getId(), $userB->getId()]);
            $this->assertContains($message->getReceiver()->getId(), [$userA->getId(), $userB->getId()]);
        }

        // assert chronological order
        $timestamps = array_map(function (PrivateChatMessage $m) {
            return $m->getTimestamp()->getTimestamp();
        }, $messages);

        $sorted = $timestamps;
        sort($sorted);
        $this->assertEquals($sorted, $timestamps, 'Messages should be ordered chronologically');

        // test limit
        $limit = 5;
        $limitedMessages = $repo->getChatHistoryByChatId($chatId, $limit);
        $this->assertCount($limit, $limitedMessages, 'Limited messages should match the limit');
    }

    /**
     * Test get last message by chat id
     *
     * @return void
     */
    public function testGetLastMessageByChatId(): void
    {
        // create test data for chat
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $this->assertGreaterThanOrEqual(2, count($users), 'There should be at least 2 users for chat test');

        $userA = $users[0];
        $userB = $users[1];

        $ids = [(int)$userA->getId(), (int)$userB->getId()];
        sort($ids);
        $chatId = hash('sha256', $ids[0] . '_' . $ids[1]);

        /** @var \App\Repository\PrivateChatMessageRepository $repo */
        $repo = $this->entityManager->getRepository(PrivateChatMessage::class);

        // get last message
        $lastMessage = $repo->getLastMessageByChatId($chatId);

        // assert that we get an object
        $this->assertInstanceOf(PrivateChatMessage::class, $lastMessage, 'Last message should be an object');

        // assert that the chat id is correct
        $this->assertEquals($chatId, $lastMessage->getChatId(), 'Chat id should match');

        // assert that the sender and receiver are correct
        $this->assertContains($lastMessage->getSender()->getId(), [$userA->getId(), $userB->getId()]);
        $this->assertContains($lastMessage->getReceiver()->getId(), [$userA->getId(), $userB->getId()]);
    }
}
