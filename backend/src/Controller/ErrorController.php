<?php

namespace App\Controller;

use Throwable;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\JsonContent;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ErrorController
 *
 * Controller for error handling
 *
 * @package App\Controller
 */
class ErrorController extends AbstractController
{
    /**
     * Handle error by code
     *
     * @param Request $request Request object
     *
     * @return JsonResponse Return error message as json response
     */
    #[Tag(name: "Error")]
    #[Security(name: null)]
    #[Parameter(name: 'code', in: 'query', description: 'Error code', required: true)]
    #[Response(
        response: JsonResponse::HTTP_OK,
        description: 'The error message',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'error'),
                new Property(property: 'message', type: 'string', example: 'Internal server error')
            ]
        )
    )]
    #[Route('/error', methods: ['GET'], name: 'error_by_code')]
    public function handleError(Request $request): JsonResponse
    {
        // get error code
        $code = $request->query->get('code', '400');

        // get error code as integer
        $code = intval($code);

        // error messages list
        $messages = [
            JsonResponse::HTTP_BAD_REQUEST => 'Bad request.',
            JsonResponse::HTTP_UNAUTHORIZED => 'Unauthorized.',
            JsonResponse::HTTP_FORBIDDEN => 'Forbidden.',
            JsonResponse::HTTP_NOT_FOUND => 'This route does not exist.',
            JsonResponse::HTTP_METHOD_NOT_ALLOWED => 'This request method is not allowed.',
            JsonResponse::HTTP_UPGRADE_REQUIRED => 'Upgrade required.',
            JsonResponse::HTTP_TOO_MANY_REQUESTS => 'Too many requests.',
            JsonResponse::HTTP_INTERNAL_SERVER_ERROR => 'Internal server error.',
            JsonResponse::HTTP_SERVICE_UNAVAILABLE => 'Service currently unavailable.'
        ];

        // get error message
        $message = $messages[$code] ?? 'Unknown error.';

        // return error message as json response
        return $this->json([
            'status' => 'error',
            'message' => $message
        ], $code);
    }

    /**
     * Handle not found error
     *
     * @return JsonResponse Return not found error message as json response
     */
    #[Tag(name: "Error")]
    #[Security(name: null)]
    #[Response(
        response: JsonResponse::HTTP_OK,
        description: 'The not found error message',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'error'),
                new Property(property: 'message', type: 'string', example: 'This route does not exist')
            ]
        )
    )]
    #[Route('/error/notfound', methods:['GET'], name: 'error_not_found')]
    public function handleNotFoundError(): JsonResponse
    {
        return $this->json([
            'status' => 'error',
            'message' => 'This route does not exist!'
        ], JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Handle error exception
     *
     * @param Throwable $exception The exception object
     *
     * @return JsonResponse Return error message as json response
     */
    public function handleExceptionError(Throwable $exception): JsonResponse
    {
        // get exception code
        $statusCode = $exception instanceof HttpException
            ? $exception->getStatusCode() : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        // return error response as json
        return new JsonResponse(json_encode([
            'status' => 'error',
            'message' => $exception->getMessage()
        ], JSON_UNESCAPED_SLASHES), $statusCode, json: true);
    }
}
