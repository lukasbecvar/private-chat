<?php

namespace App\Controller\User;

use Exception;
use OpenApi\Attributes\Tag;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\JsonContent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class UserInfoController
 *
 * API controller for getting user info
 *
 * @package App\Controller\User
 */
class UserInfoController extends AbstractController
{
    private UserManager $userManager;
    private ErrorManager $errorManager;

    public function __construct(UserManager $userManager, ErrorManager $errorManager)
    {
        $this->userManager = $userManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Get user info (get self user info)
     *
     * @param Security $security The security object (for get user)
     *
     * @return JsonResponse The user info response
     */
    #[Tag(name: "User")]
    #[Response(
        response: JsonResponse::HTTP_OK,
        description: 'The user data (self)',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'success'),
                new Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new Property(property: 'email', type: 'string', example: 'lukas@becvar.xyz'),
                        new Property(property: 'first-name', type: 'string', example: 'Lukas'),
                        new Property(property: 'last-name', type: 'string', example: 'Becvar'),
                        new Property(property: 'roles', type: 'array', items: new Items(type: 'string'), example: ["ROLE_USER"]),
                        new Property(property: 'register-time', type: 'string', format: 'date-time', example: '2024-11-17 07:31:27'),
                        new Property(property: 'last-login-time', type: 'string', format: 'date-time', example: '2025-01-31 10:50:29'),
                        new Property(property: 'ip-address', type: 'string', example: '172.19.0.1'),
                        new Property(property: 'user-agent', type: 'string', example: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36'),
                        new Property(property: 'status', type: 'string', example: 'active'),
                        new Property(property: 'is-active', type: 'boolean', example: true)
                    ]
                )
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_UNAUTHORIZED,
        description: 'The user not found message',
        content: new JsonContent(
            example: [
                "status" => "error",
                "message" => "The unauthorized message"
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_NOT_FOUND,
        description: 'The user not found message',
        content: new JsonContent(
            example: [
                "status" => "error",
                "message" => "The user not found"
            ]
        )
    )]
    #[Route('/api/user/info', methods:['GET'], name: 'user_info')]
    public function userInfo(Security $security): JsonResponse
    {
        try {
            /** @var \App\Entity\User $user */
            $user = $security->getUser();

            // check if user exists
            if ($user === null) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'user not found.'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // get user id
            $id = $user->getId();

            // check if user id found
            if ($id === null) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'user id not found.'
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            // get user info
            $userInfo = $this->userManager->getUserInfo($id);

            // return user info
            return $this->json([
                'status' => 'success',
                'data' => $userInfo
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorManager->handleError(
                message: 'User info get failed',
                exceptionMessage: $e->getMessage(),
                code: ($e->getCode() === 0 ? JsonResponse::HTTP_INTERNAL_SERVER_ERROR : $e->getCode())
            );
        }
    }
}
