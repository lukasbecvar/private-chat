<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ErrorControllerTest
 *
 * Test cases for error controller
 *
 * @package App\Tests\Controller
 */
class ErrorControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load error route without specific error code
     *
     * @return void
     */
    public function testLoadErrorRouteWithoutCode(): void
    {
        $this->client->request('GET', '/error');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('Bad request.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse:: HTTP_BAD_REQUEST);
    }

    /**
     * Test load unauthorized error route
     *
     * @return void
     */
    public function testLoadErrorRouteWithUnauthorizedCode(): void
    {
        $this->client->request('GET', '/error?code=401');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('Unauthorized.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test load forbidden error route
     *
     * @return void
     */
    public function testLoadErrorRouteWithForbiddenCode(): void
    {
        $this->client->request('GET', '/error?code=403');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('Forbidden.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * Test load not found error route
     *
     * @return void
     */
    public function testLoadErrorRouteWithNotFoundCode(): void
    {
        $this->client->request('GET', '/error?code=404');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('This route does not exist.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Test load error route with not allowed code
     *
     * @return void
     */
    public function testLoadErrorRouteWithNotAllowedCode(): void
    {
        $this->client->request('GET', '/error?code=405');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('This request method is not allowed.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test load error route with upgrade required code
     *
     * @return void
     */
    public function testLoadErrorRouteWithUpgradeRequiredCode(): void
    {
        $this->client->request('GET', '/error?code=426');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('Upgrade required.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UPGRADE_REQUIRED);
    }

    /**
     * Test load error route with too many requests code
     *
     * @return void
     */
    public function testLoadErrorRouteWithTooManyRequestsCode(): void
    {
        $this->client->request('GET', '/error?code=429');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('Too many requests.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Test load internal server error route
     *
     * @return void
     */
    public function testLoadErrorRouteWithInternalServerErrorCode(): void
    {
        $this->client->request('GET', '/error?code=500');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('Internal server error.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test load service unavailable error route
     *
     * @return void
     */
    public function testLoadErrorRouteWithServiceUnavailableCode(): void
    {
        $this->client->request('GET', '/error?code=503');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('Service currently unavailable.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * Test load not found route
     *
     * @return void
     */
    public function testLoadNotFoundRoute(): void
    {
        $this->client->request('GET', '/error/notfound');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertSame('This route does not exist!', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_NOT_FOUND);
    }
}
