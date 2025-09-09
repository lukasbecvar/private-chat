<?php

namespace App\Tests\User;

use App\Tests\CustomTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class UserInfoControllerTest
 *
 * Test cases for user info API endpoint
 *
 * @package App\Tests\User
 */
class UserInfoControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test user info when request method is invalid
     *
     * @return void
     */
    public function testUserInfoWhenRequestMethodIsNotValid(): void
    {
        $this->client->request('POST', '/api/user/info');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test user info when auth token is not provided
     *
     * @return void
     */
    public function testUserInfoWhenAuthTokenIsNotProvided(): void
    {
        $this->client->request('GET', '/api/user/info');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('JWT Token not found', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test user info when api access token is not provided
     *
     * @return void
     */
    public function testUserInfoWhenApiAccessTokenIsNotProvided(): void
    {
        $this->client->request('GET', '/api/user/info', [], [], [
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
     * Test user info when auth token is invalid
     *
     * @return void
     */
    public function testUserInfoWhenAuthTokenIsInvalid(): void
    {
        $this->client->request('GET', '/api/user/info', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token'
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Invalid JWT Token', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test get user info with valid auth token
     *
     * @return void
     */
    public function testUserInfoGetSuccess(): void
    {
        $this->client->request('GET', '/api/user/info', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('email', $responseData['data']);
        $this->assertArrayHasKey('first-name', $responseData['data']);
        $this->assertArrayHasKey('last-name', $responseData['data']);
        $this->assertArrayHasKey('roles', $responseData['data']);
        $this->assertArrayHasKey('register-time', $responseData['data']);
        $this->assertArrayHasKey('last-login-time', $responseData['data']);
        $this->assertArrayHasKey('ip-address', $responseData['data']);
        $this->assertArrayHasKey('user-agent', $responseData['data']);
        $this->assertArrayHasKey('status', $responseData['data']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }
}
