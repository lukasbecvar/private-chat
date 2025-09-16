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
            $this->assertNotNull($message->getSender(), 'Sender should not be null');
            $this->assertNotNull($message->getReceiver(), 'Receiver should not be null');

            // assert chat reciver and sender ids are correct
            $this->assertEquals($chatId, $message->getChatId());
            $this->assertContains($message->getSender()->getId(), [$userA->getId(), $userB->getId()]);
            $this->assertContains($message->getReceiver()->getId(), [$userA->getId(), $userB->getId()]);
        }

        // assert chronological order
        $timestamps = array_map(function (PrivateChatMessage $m) {
            $this->assertNotNull($m->getTimestamp(), 'Timestamp should not be null');
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
}
