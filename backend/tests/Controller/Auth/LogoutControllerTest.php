<?php

namespace App\Tests\Controller\Auth;

use App\Tests\CustomTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LogoutControllerTest
 *
 * Test cases for logout API endpoint
 *
 * @package App\Tests\Controller\Auth
 */
class LogoutControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test logout when request method is invalid
     *
     * @return void
     */
    public function testLogoutWhenRequestMethodIsNotValid(): void
    {
        $this->client->request('GET', '/api/auth/logout');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test logout when api access token is not provided
     *
     * @return void
     */
    public function testLogoutWhenApiAccessTokenIsNotProvided(): void
    {
        $this->client->request('POST', '/api/auth/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Invalid access token.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test logout when token is blank
     *
     * @return void
     */
    public function testLogoutWhenTokenIsBlank(): void
    {
        $this->client->request('POST', '/api/auth/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN']
        ], json_encode([
            'token' => ''
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertEquals('JWT Token not found', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test logout when token is invalid
     *
     * @return void
     */
    public function testLogoutWhenTokenIsInvalid(): void
    {
        $this->client->request('POST', '/api/auth/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN']
        ], json_encode([
            'token' => 'invalid-token'
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertEquals('JWT Token not found', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test logout when token is valid
     *
     * @return void
     */
    public function testLogoutWhenTokenIsValid(): void
    {
        // login request
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN']
        ], json_encode([
            'username' => 'test',
            'password' => 'test'
        ]) ?: null);

        // get jwt token from cookie
        $loginResponse = $this->client->getResponse();
        $cookies = $loginResponse->headers->getCookies();
        $bearerCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'BEARER') {
                $bearerCookie = $cookie;
                break;
            }
        }
        $this->assertNotNull($bearerCookie, 'BEARER cookie was not set on login');

        // logout request with BEARER cookie
        $this->client->request('POST', '/api/auth/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_COOKIE' => 'BEARER=' . $bearerCookie->getValue()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertSame('success', $responseData['status']);
        $this->assertSame('User successfully logged out!', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }
}
