<?php

namespace App\Controller\Chat;

use OpenApi\Attributes\Tag;
use App\Manager\ChatManager;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\JsonContent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ChatListController
 *
 * API controller for getting chat list
 *
 * @package App\Controller\Chat
 */
class ChatListController extends AbstractController
{
    private ChatManager $chatManager;

    public function __construct(ChatManager $chatManager)
    {
        $this->chatManager = $chatManager;
    }

    /**
     * Get available chats list
     *
     * @param Security $security The security object (for get user)
     *
     * @return JsonResponse The chat list response
     */
    #[Tag(name: 'Chat')]
    #[Response(
        response: JsonResponse::HTTP_OK,
        description: 'The available chats list',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'success'),
                new Property(property: 'data', type: 'array', items: new Items(type: 'object'), example: [
                    [
                        'user-id' => 1,
                        'chat-id' => 'id-hash',
                        'roles' => ['ROLE_USER'],
                        'is-admin' => true,
                        'username' => 'John',
                        'profile-picture' => 'base64-encoded-image'
                    ]
                ])
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_UNAUTHORIZED,
        description: 'The user not found message',
        content: new JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The unauthorized message'
            ]
        )
    )]
    #[Route('/api/chat/chat-list', methods:['GET'], name: 'chat_list')]
    public function chatList(Security $security): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $security->getUser();

        if (!$user?->getId()) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not found or invalid.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // get current logged user id
        $currentUserId = $user->getId();

        // get available chats list
        $chatList = $this->chatManager->getAvailableChatsList($currentUserId);

        // return chat list
        return $this->json([
            'status' => 'success',
            'data' => $chatList
        ], JsonResponse::HTTP_OK);
    }
}
