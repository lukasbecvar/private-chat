<?php

namespace App\Controller\Auth;

use Exception;
use OpenApi\Attributes\Tag;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\JsonContent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class SecurityController
 *
 * The controller for user authentication and authorization system
 *
 * @package App\Controller\Auth
 */
class SecurityController extends AbstractController
{
    private AuthManager $authManager;
    private ErrorManager $errorManager;

    public function __construct(AuthManager $authManager, ErrorManager $errorManager)
    {
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Logout user from system with auth token invaldation
     *
     * @param Request $request The request object
     * @param Security $security The security object (for get user)
     *
     * @return JsonResponse The logout status response
     */
    #[Tag(name: "Auth")]
    #[Response(
        response: JsonResponse::HTTP_OK,
        description: 'The logout successful message',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'success'),
                new Property(property: 'message', type: 'string', example: 'User successfully logged out!')
            ]
        )
    )]
    #[Response(
        response: JsonResponse::HTTP_UNAUTHORIZED,
        description: 'The JWT token Invalid message',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'error'),
                new Property(property: 'message', type: 'string', example: 'Invalid JWT token!')
            ]
        )
    )]
    #[Route('/api/auth/logout', methods:['POST'], name: 'auth_logout')]
    public function logout(Request $request, Security $security): JsonResponse
    {
        // get auth token from request
        $authToken = $this->authManager->getAuthTokenFromRequest($request);

        // check if auth token set in request
        if ($authToken == null) {
            return $this->json([
                'status' => 'error',
                'message' => 'JWT token not set in request!'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            // invalidate token auth token
            $this->authManager->logout($authToken, $security);

            // return success response
            return $this->json([
                'status' => 'success',
                'message' => 'User successfully logged out!'
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Logout process error',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }
}
