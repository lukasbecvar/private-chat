<?php

namespace App\Tests\Controller\Chat;

use App\Tests\CustomTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ChatHistoryControllerTest
 *
 * Test cases for chat history API endpoint
 *
 * @package App\Tests\Controller\Chat
 */
class ChatHistoryControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test chat history when request method is invalid
     *
     * @return void
     */
    public function testChatHistoryWhenRequestMethodIsNotValid(): void
    {
        $this->client->request('POST', '/api/chat/history');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test chat history when auth token is not provided
     *
     * @return void
     */
    public function testChatHistoryWhenAuthTokenIsNotProvided(): void
    {
        $this->client->request('GET', '/api/chat/history');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('JWT Token not found', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test chat history when api access token is not provided
     *
     * @return void
     */
    public function testChatHistoryWhenApiAccessTokenIsNotProvided(): void
    {
        $this->client->request('GET', '/api/chat/history', [], [], [
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
     * Test chat history when auth token is invalid
     *
     * @return void
     */
    public function testChatHistoryWhenAuthTokenIsInvalid(): void
    {
        $this->client->request('GET', '/api/chat/history', [], [], [
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
     * Test chat history when chat id parameter is missing
     *
     * @return void
     */
    public function testChatHistoryWhenChatIdParameterIsMissing(): void
    {
        $this->client->request('GET', '/api/chat/history', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Missing required parameter: chat_id', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test chat history when chat id not found
     *
     * @return void
     */
    public function testChatHistoryWhenChatIdNotFound(): void
    {
        $this->client->request('GET', '/api/chat/history', ['chat_id' => 'non-existing-chat'], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('This chat is empty', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Test chat history when user is not allowed to access chat
     *
     * @return void
     */
    public function testChatHistoryWhenUserIsNotAllowedToAccessChat(): void
    {
        $this->client->request('GET', '/api/chat/history', ['chat_id' => 'bf0d7fe53114d9ae2ca1e0845e1bb0ed917b29e36cc2ed21a2d05c61cf5675d6'], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('You are not allowed to access this chat', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * Test chat history when after_id parameter is used with limit or offset
     *
     * @return void
     */
    public function testChatHistoryWhenAfterIdParameterIsUsedWithLimitOrOffset(): void
    {
        $this->client->request('GET', '/api/chat/history', ['chat_id' => '9d21ef536a074f046f12156c0f2893f85874b2a281e14520122571455a4a6c98', 'after_id' => 1, 'limit' => 5, 'offset' => 5], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('The after_id parameter cannot be used with the limit or offset parameters', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test chat history with valid chat id
     *
     * @return void
     */
    public function testChatHistoryWithValidChatId(): void
    {
        $this->client->request('GET', '/api/chat/history', ['chat_id' => '9d21ef536a074f046f12156c0f2893f85874b2a281e14520122571455a4a6c98'], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('messages-count', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertSame('success', $responseData['status']);
        $this->assertIsArray($responseData['data']);
        $this->assertNotEmpty($responseData['data']);
        $this->assertArrayHasKey('id', $responseData['data'][0]);
        $this->assertArrayHasKey('sender', $responseData['data'][0]);
        $this->assertArrayHasKey('receiver', $responseData['data'][0]);
        $this->assertArrayHasKey('message', $responseData['data'][0]);
        $this->assertArrayHasKey('timestamp', $responseData['data'][0]);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }

    /**
     * Test chat history with limit parameter
     *
     * @return void
     */
    public function testChatHistoryWithLimitParameter(): void
    {
        $this->client->request('GET', '/api/chat/history', ['chat_id' => '9d21ef536a074f046f12156c0f2893f85874b2a281e14520122571455a4a6c98', 'limit' => 5], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('messages-count', $responseData);
        $this->assertIsInt($responseData['messages-count']);
        $this->assertEquals(5, $responseData['messages-count']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }

    /**
     * Test chat history with offset parameter
     *
     * @return void
     */
    public function testChatHistoryWithOffsetParameter(): void
    {
        $this->client->request('GET', '/api/chat/history', ['chat_id' => '9d21ef536a074f046f12156c0f2893f85874b2a281e14520122571455a4a6c98', 'offset' => 5], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('messages-count', $responseData);
        $this->assertIsInt($responseData['messages-count']);
        $this->assertEquals(45, $responseData['messages-count']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }
}
