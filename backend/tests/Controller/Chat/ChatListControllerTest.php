<?php

namespace App\Tests\Controller\Chat;

use App\Tests\CustomTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ChatListControllerTest
 *
 * Test cases for chat list API endpoint
 *
 * @package App\Tests\Controller\Chat
 */
class ChatListControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test chat list when request method is invalid
     *
     * @return void
     */
    public function testChatListWhenRequestMethodIsNotValid(): void
    {
        $this->client->request('POST', '/api/chat/chat-list');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test chat list when auth token is not provided
     *
     * @return void
     */
    public function testChatListWhenAuthTokenIsNotProvided(): void
    {
        $this->client->request('GET', '/api/chat/chat-list');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('JWT Token not found', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test chat list when api access token is not provided
     *
     * @return void
     */
    public function testChatListWhenApiAccessTokenIsNotProvided(): void
    {
        $this->client->request('GET', '/api/chat/chat-list', [], [], [
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
     * Test chat list when auth token is invalid
     *
     * @return void
     */
    public function testChatListWhenAuthTokenIsInvalid(): void
    {
        $this->client->request('GET', '/api/chat/chat-list', [], [], [
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
     * Test chat list with valid auth token
     *
     * @return void
     */
    public function testChatListWithValidAuthToken(): void
    {
        $this->client->request('GET', '/api/chat/chat-list', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertSame('success', $responseData['status']);
        $this->assertIsArray($responseData['data']);
        $this->assertNotEmpty($responseData['data']);
        $this->assertArrayHasKey('user-id', $responseData['data'][0]);
        $this->assertArrayHasKey('chat-id', $responseData['data'][0]);
        $this->assertArrayHasKey('roles', $responseData['data'][0]);
        $this->assertArrayHasKey('is-admin', $responseData['data'][0]);
        $this->assertArrayHasKey('username', $responseData['data'][0]);
        $this->assertArrayHasKey('profile-picture', $responseData['data'][0]);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }
}
