<?php

namespace App\Controller\Chat;

use App\DTO\MessageDTO;
use OpenApi\Attributes\Tag;
use App\Manager\ChatManager;
use App\Manager\UserManager;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class SendMessageController
 *
 * API controller for sending message to chat
 *
 * @package App\Controller\Chat
 */
class SendMessageController extends AbstractController
{
    private ChatManager $chatManager;
    private UserManager $userManager;
    private ValidatorInterface $validator;

    public function __construct(ChatManager $chatManager, UserManager $userManager, ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->userManager = $userManager;
        $this->chatManager = $chatManager;
    }

    /**
     * Send message to chat
     *
     * @param Request $request The request object
     * @param Security $security The security object (for get user)
     *
     * @return JsonResponse Status message response
     */
    #[Tag(name: 'Chat')]
    #[RequestBody(
        required: true,
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'message', type: 'string', example: 'Hello world!'),
                new Property(property: 'receiver_id', type: 'integer', example: 10)
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_OK,
        description: 'The message sent',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'success'),
                new Property(property: 'message', type: 'string', example: 'Message sent successfully!')
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_UNAUTHORIZED,
        description: 'The user not found message',
        content: new JsonContent(
            example: [
                "status" => 'error',
                "message" => 'The unauthorized message'
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_BAD_REQUEST,
        description: 'Validation failed',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'error'),
                new Property(property: 'message', type: 'string', example: 'Missing required parameter')
            ]
        )
    )]
    #[Route('/api/chat/send', methods: ['POST'], name: 'send_message')]
    public function sendMessage(Request $request, Security $security): JsonResponse
    {
        // testing error
        return $this->json([
            'status' => 'error',
            'message' => 'Testing error'
        ], JsonResponse::HTTP_BAD_REQUEST);

        /** @var \App\Entity\User|null $user */
        $user = $security->getUser();

        if (!$user?->getId()) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not found or invalid.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // decode JSON body
        $data = json_decode($request->getContent(), true) ?? [];

        // get request data
        $message = $data['message'] ?? null;
        $receiverId = $data['receiver_id'] ?? null;

        // check if message and receiver_id are set
        if (!$message || !$receiverId) {
            return $this->json([
                'status' => 'error',
                'message' => 'Parameters message and receiver_id are required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // check if receiver id is active
        $receiverStatus = $this->userManager->getUserStatus($receiverId);
        if ($receiverStatus != 'active') {
            return $this->json([
                'status' => 'error',
                'message' => 'You are not allowed to access this chat because receiver user status is: ' . $receiverStatus
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        // init message dto
        $messageObj = new MessageDTO();
        $messageObj->message = $message;

        // validate message dto
        $errors = $this->validator->validate($messageObj);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return $this->json([
                    'status' => 'error',
                    'message' => $error->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // save message to chat
        $this->chatManager->saveMessageToChat($user->getId(), (int)$receiverId, $message);

        // return success response
        return $this->json([
            'status' => 'success',
            'message' => 'Message sent successfully!'
        ], JsonResponse::HTTP_OK);
    }
}
