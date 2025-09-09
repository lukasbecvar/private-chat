<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserFixtures
 *
 * Testing data fixtures for user entity
 *
 * @package App\DataFixtures
 */
class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * Load user fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // create test user
        $user = new User();
        $user->setEmail('test@test.test')
            ->setFirstName('test')
            ->setLastName('User')
            ->setRoles(['ROLE_USER', 'ROLE_ADMIN'])
            ->setPassword($this->userPasswordHasher->hashPassword($user, 'test'))
            ->setRegisterTime($faker->dateTimeBetween('-1 year', 'now'))
            ->setLastLoginTime($faker->dateTimeBetween('-6 months', 'now'))
            ->setIpAddress($faker->ipv4)
            ->setUserAgent($faker->userAgent)
            ->setStatus('active');

        // persist test user
        $manager->persist($user);

        // create testing user entities
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();

            // hash password
            $passwordHash = $this->userPasswordHasher->hashPassword($user, 'password');

            // set user properties
            $user->setEmail("user$i@example.com")
                ->setFirstName($faker->firstName)
                ->setLastName($faker->lastName)
                ->setRoles(['ROLE_USER'])
                ->setPassword($passwordHash)
                ->setRegisterTime($faker->dateTimeBetween('-1 year', 'now'))
                ->setLastLoginTime($faker->dateTimeBetween('-6 months', 'now'))
                ->setIpAddress($faker->ipv4)
                ->setUserAgent($faker->userAgent)
                ->setStatus('active');

            // persist user entity
            $manager->persist($user);
        }

        // flush user entities to database
        $manager->flush();
    }
}
