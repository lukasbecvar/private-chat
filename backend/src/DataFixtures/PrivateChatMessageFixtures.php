<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Util\ChatUtil;
use App\Entity\PrivateChatMessage;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Class PrivateChatMessageFixtures
 *
 * Testing data fixtures for private chat message entity
 *
 * @package App\DataFixtures
 */
class PrivateChatMessageFixtures extends Fixture implements DependentFixtureInterface
{
    private ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    /**
     * Get the dependencies for ensure that this fixture is loaded after user fixtures
     *
     * @return array<class-string<\Doctrine\Common\DataFixtures\FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    /**
     * Load private chat messages
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // get all users from DB
        $users = $manager->getRepository(User::class)->findAll();

        // initialize empty array for storing all messages
        $allMessages = [];

        foreach ($users as $sender) {
            foreach ($users as $receiver) {
                if ($sender->getId() >= $receiver->getId()) {
                    continue;
                }

                for ($m = 0; $m < 500; $m++) {
                    $message = new PrivateChatMessage();

                    // generate chat identifier with deterministic and symmetric method
                    $chatId = $this->chatUtil->generateChatId((int)$sender->getId(), (int)$receiver->getId());

                    // generate unique timestamp
                    $timestamp = $faker->dateTimeBetween('-1 year', 'now');

                    // set message properties
                    $message->setSender($sender)
                            ->setReceiver($receiver)
                            ->setMessage($faker->sentence(8))
                            ->setTimestamp($timestamp)
                            ->setChatId($chatId);

                    // add message to messages array (for sorting)
                    $allMessages[] = $message;
                }
            }
        }

        // sort messages by timestamp
        usort($allMessages, fn($a, $b) => $a->getTimestamp() <=> $b->getTimestamp());

        // persist messages
        foreach ($allMessages as $message) {
            $manager->persist($message);
        }

        // flush messages to database
        //$manager->flush();
    }
}
