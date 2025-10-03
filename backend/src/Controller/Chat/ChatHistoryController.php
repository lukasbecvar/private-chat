<?php

namespace App\Controller\Chat;

use OpenApi\Attributes\Tag;
use App\Manager\ChatManager;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Parameter;
use App\Entity\PrivateChatMessage;
use OpenApi\Attributes\JsonContent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ChatHistoryController
 *
 * API controller for getting chat history
 *
 * @package App\Controller\Chat
 */
class ChatHistoryController extends AbstractController
{
    private ChatManager $chatManager;

    public function __construct(ChatManager $chatManager)
    {
        $this->chatManager = $chatManager;
    }

    /**
     * Get chat history
     *
     * @param Request $request The request object
     * @param Security $security The security object (for get user)
     *
     * @return JsonResponse The chat history response
     */
    #[Tag(name: 'Chat')]
    #[Parameter(name: 'chat_id', in: 'query', description: 'The chat id', required: true)]
    #[Parameter(name: 'after_id', in: 'query', description: 'The id of the last message in the previous page', required: false)]
    #[Parameter(name: 'limit', in: 'query', description: 'The limit of messages (cannot be used with after_id parameter)', required: false)]
    #[Parameter(name: 'offset', in: 'query', description: 'The offset for pagination (cannot be used with after_id parameter)', required: false)]
    #[Response(
        response: JsonResponse::HTTP_OK,
        description: 'The chat history',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'success'),
                new Property(property: 'messages-count', type: 'integer', example: 10),
                new Property(property: 'data', type: 'array', items: new Items(type: 'object'), example: [
                    [
                        'id' => 1,
                        'sender' => 1,
                        'receiver' => 2,
                        'message' => 'Hello!',
                        'timestamp' => '2024-11-17 07:31:27'
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
    #[Response(
        response: JsonResponse::HTTP_BAD_REQUEST,
        description: 'The chat id is missing',
        content: new JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Missing required parameter: chat_id'
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_NOT_FOUND,
        description: 'The chat not found message',
        content: new JsonContent(
            example: [
                'status' => 'error',
                'message' => 'This chat is empty'
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_FORBIDDEN,
        description: 'The user is not allowed to access this chat',
        content: new JsonContent(
            example: [
                'status' => 'error',
                'message' => 'You are not allowed to access this chat'
            ]
        )
    )]
    #[Route('/api/chat/history', methods: ['GET'], name: 'chat_history')]
    public function chatHistory(Request $request, Security $security): JsonResponse
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

        // get query params
        $chatId = $request->query->get('chat_id');
        $afterId = (int) $request->query->get('after_id') ?: null;
        $limit = (int) $request->query->getInt('limit', 0) ?: null;
        $offset = (int) $request->query->getInt('offset', 0) ?: null;

        // check if after_id is used with limit or offset
        if ($afterId !== null && ($limit !== null || $offset !== null)) {
            return $this->json([
                'status' => 'error',
                'message' => 'The after_id parameter cannot be used with the limit or offset parameters'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // check if chat id parameter is set
        if (!$chatId) {
            return $this->json([
                'status' => 'error',
                'message' => 'Missing required parameter: chat_id'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // check if chat exists
        if (!$this->chatManager->checkIfChatExists($chatId)) {
            return $this->json([
                'status' => 'error',
                'message' => 'This chat is empty'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // check if user has access to chat
        if (!$this->chatManager->userHasAccessToChat($chatId, $currentUserId)) {
            return $this->json([
                'status' => 'error',
                'message' => 'You are not allowed to access this chat'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        /** @var PrivateChatMessage[] $messages */
        $messages = $this->chatManager->getChatHistory($chatId, $limit, $offset, $afterId);

        // get chat message count
        $messageCount = $this->chatManager->getChatHistoryCount($chatId);

        return $this->json([
            'status' => 'success',
            'messages-count' => $messageCount,
            'data' => $messages
        ], JsonResponse::HTTP_OK);
    }
}
