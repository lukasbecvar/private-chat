<?php

namespace App\Controller;

use App\Util\AppUtil;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\JsonContent;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class IndexController
 *
 * Main index controller for check api status
 *
 * @package App\Controller
 */
class IndexController extends AbstractController
{
    private AppUtil $appUtil;

    public function __construct(AppUtil $appUtil)
    {
        $this->appUtil = $appUtil;
    }

    /**
     * Index route for check api status
     *
     * @return JsonResponse Return backend status as json response
     */
    #[Tag(name: "Index")]
    #[Security(name: null)]
    #[Response(
        response: JsonResponse::HTTP_OK,
        description: 'The API status',
        content: new JsonContent(
            type: 'object',
            properties: [
                new Property(property: 'status', type: 'string', example: 'success'),
                new Property(property: 'message', type: 'string', example: 'status message'),
                new Property(property: 'version', type: 'string', example: 'app version')
            ]
        )
    )]
    #[Route('/', methods:['GET'], name: 'main_index')]
    public function index(): JsonResponse
    {
        // get backend status
        $responseData = [
            'status' => 'success',
            'message' => 'private-chat api is running!',
            'version' => $_ENV['APP_VERSION']
        ];

        // add warning if app is running in dev mode
        if ($this->appUtil->isDevMode()) {
            $responseData['warning'] = 'App is running in dev mode (you can find documentation at /api/doc)!';
        }

        // return backend status as json response
        return new JsonResponse(json_encode($responseData, JSON_UNESCAPED_SLASHES), JsonResponse::HTTP_OK, json: true);
    }
}
