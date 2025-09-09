<?php

namespace App\Repository;

use App\Entity\Log;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class LogRepository
 *
 * Repository for log database entity
 *
 * @extends ServiceEntityRepository<Log>
 *
 * @package App\Repository
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * Get logs by status with pagination
     *
     * @param string $status The status of the logs
     * @param int $page The page number
     * @param int $limit The limit of logs per page (default: 50)
     *
     * @return array<Log> Logs list
     */
    public function findByStatus(string $status, int $page, int $limit = 50): array
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->setParameter('status', $status)
            ->orderBy('l.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get logs by user id with pagination
     *
     * @param int $userId The user id
     * @param int $page The page number
     * @param int $limit The limit of logs per page (default: 50)
     *
     * @return array<Log> Logs list
     */
    public function findByUserId(int $userId, int $page, int $limit = 50): array
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->where('l.user_id = :user_id')
            ->setParameter('user_id', $userId)
            ->orderBy('l.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get logs by ip address with pagination
     *
     * @param string $ipAddress The ip address
     * @param int $page The page number
     * @param int $limit The limit of logs per page (default: 50)
     *
     * @return array<Log> Logs list
     */
    public function findByIpAddress(string $ipAddress, int $page, int $limit = 50): array
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->where('l.ip_address = :ip_address')
            ->setParameter('ip_address', $ipAddress)
            ->orderBy('l.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find logs with username by given criteria
     *
     * @param array<string, mixed> $criteria The search criteria
     * @param int $page The page number
     * @param int $limit The limit of logs per page (default: 50)
     *
     * @return array<mixed> Logs list
     */
    public function findLogsWithUsername(array $criteria, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->select('l.id, l.name, l.message, l.time, l.ip_address, u.email as username')
            ->leftJoin(User::class, 'u', 'WITH', 'l.user_id = u.id')
            ->orderBy('l.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // add criteria to query
        foreach ($criteria as $field => $value) {
            $queryBuilder->andWhere("l.{$field} = :{$field}")->setParameter($field, $value);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
