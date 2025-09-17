<?php

namespace App\Tests\Controller\Chat;

use App\Tests\CustomTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SendMessageControllerTest
 *
 * Test cases for send message API endpoint
 *
 * @package App\Tests\Controller\Chat
 */
class SendMessageControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test send message when request method is invalid
     *
     * @return void
     */
    public function testSendMessageWhenRequestMethodIsNotValid(): void
    {
        $this->client->request('GET', '/api/chat/send');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test send message when api access token is not provided
     *
     * @return void
     */
    public function testSendMessageWhenApiAccessTokenIsNotProvided(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
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
     * Test send message when auth token is not provided
     *
     * @return void
     */
    public function testSendMessageWhenAuthTokenIsNotProvided(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN']
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('JWT Token not found', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test send message when auth token is invalid
     *
     * @return void
     */
    public function testSendMessageWhenAuthTokenIsInvalid(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
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
     * Test send message when receiver id parameter is missing
     *
     * @return void
     */
    public function testSendMessageWhenReceiverIdParameterIsMissing(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'message' => 'Hello world!'
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Parameters message and receiver_id are required', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test send message when message parameter is missing
     *
     * @return void
     */
    public function testSendMessageWhenMessageParameterIsMissing(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'receiver_id' => 1
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Parameters message and receiver_id are required', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test send message when receiver id is not found
     *
     * @return void
     */
    public function testSendMessageWhenReceiverIdIsNotFound(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'message' => 'Hello world!',
            'receiver_id' => 31465
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('User id: 31465 not found', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Test send message when receiver status is not active
     *
     * @return void
     */
    public function testSendMessageWhenReceiverStatusIsNotActive(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'message' => 'Hello world!',
            'receiver_id' => 11
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('You are not allowed to access this chat because receiver user status is: banned', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * Test send message when message is blank
     *
     * @return void
     */
    public function testSendMessageWhenMessageIsBlank(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'message' => '',
            'receiver_id' => 1
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Parameters message and receiver_id are required', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test send message when message is too long
     *
     * @return void
     */
    public function testSendMessageWhenMessageIsTooLong(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'message' => str_repeat('a', 2001),
            'receiver_id' => 1
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('The message value must have at most 2000 characters.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test send message when message is valid with success response
     *
     * @return void
     */
    public function testSendMessageWhenMessageIsValidWithSuccessResponse(): void
    {
        $this->client->request('POST', '/api/chat/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'message' => 'Hello world!',
            'receiver_id' => 1
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertSame('success', $responseData['status']);
        $this->assertSame('Message sent successfully!', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }
}
