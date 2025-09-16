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
     * Get chat history by chat id
     *
     * @param string $chatId The unique chat identifier
     * @param int|null $limit Optional limit of messages
     * @param int|null $offset Optional offset for pagination
     *
     * @return PrivateChatMessage[]
     */
    public function getChatHistoryByChatId(string $chatId, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.chat_id = :chatId')
            ->setParameter('chatId', $chatId)
            ->orderBy('m.timestamp', 'ASC'); // chronological order

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }
}
