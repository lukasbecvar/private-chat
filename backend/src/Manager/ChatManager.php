<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\ChatUtil;
use App\Entity\PrivateChatMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PrivateChatMessageRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ChatManager
 *
 * Manager for chat system
 *
 * @package App\Manager
 */
class ChatManager
{
    private ChatUtil $chatUtil;
    private UserManager $userManager;
    private ErrorManager $errorManager;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private PrivateChatMessageRepository $privateChatMessageRepository;

    public function __construct(
        ChatUtil $chatUtil,
        UserManager $userManager,
        ErrorManager $errorManager,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        PrivateChatMessageRepository $privateChatMessageRepository
    ) {
        $this->chatUtil = $chatUtil;
        $this->userManager = $userManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->privateChatMessageRepository = $privateChatMessageRepository;
    }

    /**
     * Get available chats list
     *
     * @param int $currentUserId The current user id (for excluding self chat)
     *
     * @return list<array<string, mixed>> The available chats list
     */
    public function getAvailableChatsList(int $currentUserId): array
    {
        // initialize empty array for storing all chats
        $chatList = [];

        // get all users
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            // get user id
            $userId = $user->getId();

            // skip if user id is null
            if ($userId === null) {
                continue;
            }

            // skip if user is not active
            if (!$user->isActive()) {
                continue;
            }

            // skip current user (prevent self chat)
            if ($userId === $currentUserId) {
                continue;
            }

            // get user data
            $username = $user->getUsername();
            $profilePic = $user->getProfilePic();
            $roles = $user->getRoles();
            $isAdmin = $this->userManager->checkIfUserIsAdmin($userId);

            // get chat data
            $chatId = $this->chatUtil->generateChatId($currentUserId, $userId);

            // build chat item
            $chatList[] = [
                'user-id' => $userId,
                'chat-id' => $chatId,
                'roles' => $roles,
                'is-admin' => $isAdmin,
                'username' => $username,
                'profile-picture' => $profilePic
            ];
        }

        return $chatList;
    }

    /**
     * Check if chat exists
     *
     * @param string $chatId The chat identifier
     *
     * @return bool True if chat exists, otherwise false
     */
    public function checkIfChatExists(string $chatId): bool
    {
        $countOfMessages = $this->getChatHistoryCount($chatId);
        return $countOfMessages > 0;
    }

    /**
     * Get chat history count (repository wrapper)
     *
     * @param string $chatId The unique chat identifier
     *
     * @return int Number of messages in the chat
     */
    public function getChatHistoryCount(string $chatId): int
    {
        return $this->privateChatMessageRepository->getMessageCountByChatId($chatId);
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
        return $this->privateChatMessageRepository->userHasAccessToChat($chatId, $userId);
    }

    /**
     * Get chat history (repository wrapper)
     *
     * @param string $chatId The unique chat identifier
     * @param int|null $limit Optional limit of messages
     * @param int|null $offset Optional offset for pagination
     *
     * @return \App\Entity\PrivateChatMessage[]
     */
    public function getChatHistory(string $chatId, ?int $limit = null, ?int $offset = null): array
    {
        return $this->privateChatMessageRepository->getChatHistoryByChatId($chatId, $limit, $offset);
    }

    /**
     * Save message to chat
     *
     * @param int $senderId The sender user id
     * @param int $receiverId The receiver user id
     * @param string $message The message to save
     *
     * @return void
     */
    public function saveMessageToChat(int $senderId, int $receiverId, string $message): void
    {
        // generate chat id
        $chatId = $this->chatUtil->generateChatId($senderId, $receiverId);

        // get users ids
        $sender = $this->userManager->getUserEntityById($senderId);
        $receiver = $this->userManager->getUserEntityById($receiverId);

        // create new message object
        $messageObj = new PrivateChatMessage();

        // set message properties
        $messageObj->setMessage($message);
        $messageObj->setTimestamp(new DateTime());
        $messageObj->setChatId($chatId);
        $messageObj->setSender($sender);
        $messageObj->setReceiver($receiver);

        // save message to database
        try {
            $this->entityManager->persist($messageObj);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to save message to chat',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }
}
