<?php

namespace App\Tests\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserRepositoryTest
 *
 * Test cases for doctrine user repository
 *
 * @package App\Tests\Repository
 */
class UserRepositoryTest extends KernelTestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @phpstan-ignore-next-line */
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test find user by email
     *
     * @return void
     */
    public function testFindByEmail(): void
    {
        // testing user email
        $email = 'test@test.test';

        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        // call tested method
        $user = $userRepository->findByEmail($email);

        // assert result
        $this->assertNotNull($user, 'User should not be null');
        $this->assertEquals($email, $user->getEmail(), 'The user email should match the filter');
    }
}
