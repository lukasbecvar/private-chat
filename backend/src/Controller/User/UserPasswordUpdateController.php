<?php

namespace App\Controller\User;

use Exception;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class UserPasswordUpdateController
 *
 * API controller for update user password
 *
 * @package App\Controller\User
 */
class UserPasswordUpdateController extends AbstractController
{
    private UserManager $userManager;
    private ErrorManager $errorManager;

    public function __construct(UserManager $userManager, ErrorManager $errorManager)
    {
        $this->userManager = $userManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Update user password
     *
     * @param Security $security The security object (for get user)
     * @param Request $request The request object
     *
     * @return JsonResponse The update status response
     */
    #[OA\Patch(
        summary: 'User password update action (self password update)',
        description: 'Update password and return status',
        tags: ['User'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'new-password', type: 'string', description: 'New user password', example: 'securePassword123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: JsonResponse::HTTP_OK,
                description: 'The success password update message',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Password updated successfully')
                    ]
                )
            ),
            new OA\Response(
                response: JsonResponse::HTTP_BAD_REQUEST,
                description: 'Invalid request data message',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Could not decode request body')
                    ]
                )
            ),
            new OA\Response(
                response: JsonResponse::HTTP_UNAUTHORIZED,
                description: 'User not found message',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'User not found')
                    ]
                )
            ),
            new OA\Response(
                response: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                description: 'The update error message',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'User password update error')
                    ]
                )
            )
        ]
    )]
    #[Route('/api/user/update/password', methods:['PATCH'], name: 'user_data_update_password')]
    public function updateUserPassword(Security $security, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $security->getUser();

        // check if user found
        if ($user === null) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not found!'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // get new password from request
        $data = $request->toArray();
        $newPassword = $data['new-password'] ?? null;

        // check if new password is set
        if ($newPassword === null || empty($newPassword)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Parameter "new-password" is required!'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // check if new password is valid
        if (strlen($newPassword) < 8 || strlen($newPassword) > 128) {
            return $this->json([
                'status' => 'error',
                'message' => 'Parameter "new-password" must be between 8 and 128 characters long!'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // get user id
        $userId = $user->getId();

        // check if user id found
        if ($userId === null) {
            return $this->json([
                'status' => 'error',
                'message' => 'User id: ' . $userId . ' not found.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // update password
        try {
            $this->userManager->updateUserPassword($userId, $newPassword);
            return $this->json([
                'status' => 'success',
                'message' => 'Password updated successfully!'
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorManager->handleError(
                message: 'Export to export data',
                exceptionMessage: $e->getMessage(),
                code: ($e->getCode() === 0 ? JsonResponse::HTTP_INTERNAL_SERVER_ERROR : $e->getCode())
            );
        }
    }
}
