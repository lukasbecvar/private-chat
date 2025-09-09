<?php

namespace App\Tests\Repository;

use App\Entity\Log;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LogRepositoryTest
 *
 * Test cases for doctrine log repository
 *
 * @package App\Tests\Repository
 */
class LogRepositoryTest extends KernelTestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @phpstan-ignore-next-line */
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test get logs by status
     *
     * @return void
     */
    public function testGetLogsByStatus(): void
    {
        /** @var \App\Repository\LogRepository $logRepository */
        $logRepository = $this->entityManager->getRepository(Log::class);

        $status = 'UNREADED';
        $logs = $logRepository->findByStatus($status, 1);

        // assert result
        $this->assertIsArray($logs, 'Logs should be returned as an array');
        $this->assertNotEmpty($logs, 'Logs should not be empty');
        $this->assertEquals($status, $logs[0]->getStatus(), 'The log status should match the filter');
    }

    /**
     * Test get logs by user id
     *
     * @return void
     */
    public function testGetLogsByUserId(): void
    {
        /** @var \App\Repository\LogRepository $logRepository */
        $logRepository = $this->entityManager->getRepository(Log::class);

        $userId = 1;
        $logs = $logRepository->findByUserId($userId, 1);

        // assert result
        $this->assertIsArray($logs, 'Logs should be returned as an array');
        $this->assertNotEmpty($logs, 'Logs should not be empty');
        $this->assertEquals($userId, $logs[0]->getUserId(), 'The log user id should match the filter');
    }

    /**
     * Test get logs by ip address
     *
     * @return void
     */
    public function testGetLogsByIpAddress(): void
    {
        /** @var \App\Repository\LogRepository $logRepository */
        $logRepository = $this->entityManager->getRepository(Log::class);

        $ipAddress = '127.0.0.1';
        $logs = $logRepository->findByIpAddress($ipAddress, 1);

        // assert result
        $this->assertIsArray($logs, 'Logs should be returned as an array');
        $this->assertNotEmpty($logs, 'Logs should not be empty');
        $this->assertEquals($ipAddress, $logs[0]->getIpAddress(), 'The log IP address should match the filter');
    }
}
