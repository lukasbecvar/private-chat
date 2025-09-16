<?php

namespace App\Manager;

use App\Util\ChatUtil;
use App\Repository\UserRepository;

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

    public function __construct(ChatUtil $chatUtil, UserManager $userManager, UserRepository $userRepository)
    {
        $this->chatUtil = $chatUtil;
        $this->userManager = $userManager;
        $this->userRepository = $userRepository;
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
}
