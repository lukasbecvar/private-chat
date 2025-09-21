<?php

namespace App\Repository;

use App\Entity\PrivateChatMessage;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class PrivateChatMessageRepository
 *
 * Repository for private chat message database entity
 *
 * @extends ServiceEntityRepository<PrivateChatMessage>
 *
 * @package App\Repository
 */
class PrivateChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrivateChatMessage::class);
    }

    /**
     * Get total number of messages in given chat
     *
     * @param string $chatId The chat identifier
     *
     * @return int Number of messages in the chat
     */
    public function getMessageCountByChatId(string $chatId): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.chat_id = :chatId')
            ->setParameter('chatId', $chatId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Check if user has access to given chat (as sender or receiver)
     *
     * @param string $chatId The chat identifier
     * @param int $userId The user identifier
     *
     * @return bool True if user is part of the chat, otherwise false
     */
    public function userHasAccessToChat(string $chatId, int $userId): bool
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.chat_id = :chatId')
            ->andWhere('(m.sender = :userId OR m.receiver = :userId)')
            ->setParameter('chatId', $chatId)
            ->setParameter('userId', $userId);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Get chat history by chat id
     *
     * @param string   $chatId   The unique chat identifier
     * @param int|null $limit    Optional limit of messages
     * @param int|null $offset   Optional offset for pagination
     * @param int|null $afterId  Optional id of the last received message
     *
     * @return PrivateChatMessage[]
     */
    public function getChatHistoryByChatId(string $chatId, ?int $limit = null, ?int $offset = null, ?int $afterId = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.chat_id = :chatId')
            ->setParameter('chatId', $chatId)
            ->orderBy('m.timestamp', 'ASC'); // chronological order

        if ($afterId !== null) {
            $qb->andWhere('m.id > :afterId')
            ->setParameter('afterId', $afterId);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the last message in a chat by chatId
     *
     * @param string $chatId
     * @return PrivateChatMessage|null
     */
    public function getLastMessageByChatId(string $chatId): ?PrivateChatMessage
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.chat_id = :chatId')
            ->setParameter('chatId', $chatId)
            ->orderBy('m.timestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
