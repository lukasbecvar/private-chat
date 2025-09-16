<?php

namespace App\Manager;

use App\Util\ChatUtil;
use App\Repository\UserRepository;
use App\Repository\PrivateChatMessageRepository;

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
    private UserRepository $userRepository;
    private PrivateChatMessageRepository $privateChatMessageRepository;

    public function __construct(
        ChatUtil $chatUtil,
        UserManager $userManager,
        UserRepository $userRepository,
        PrivateChatMessageRepository $privateChatMessageRepository
    ) {
        $this->chatUtil = $chatUtil;
        $this->userManager = $userManager;
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
}
