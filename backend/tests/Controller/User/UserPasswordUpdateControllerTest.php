<?php

namespace App\Tests\User;

use App\Tests\CustomTestCase;
use Symfony\Component\String\ByteString;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class UserPasswordUpdateControllerTest
 *
 * Test cases for password update controller (API endpoint)
 *
 * @package App\Tests\User
 */
class UserPasswordUpdateControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test update user password when request method is not valid
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenRequestMethodIsNotValid(): void
    {
        $this->client->request('GET', '/api/user/update/password');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test update user password when auth token is not provided
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenAuthTokenIsNotProvided(): void
    {
        $this->client->request('PATCH', '/api/user/update/password');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('JWT Token not found', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test update user password when api access token is not provided
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenApiAccessTokenIsNotProvided(): void
    {
        $this->client->request('PATCH', '/api/user/update/password', [], [], [
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
     * Test update user password when auth token is invalid
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenAuthTokenIsInvalid(): void
    {
        $this->client->request('PATCH', '/api/user/update/password', [], [], [
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
     * Test update user password when new password is not provided
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenNewPasswordIsNotProvided(): void
    {
        $this->client->request('PATCH', '/api/user/update/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Request body is empty.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test update user password when new password is empty
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenPasswordIsEmpty(): void
    {
        $this->client->request('PATCH', '/api/user/update/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'new-password' => ''
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Parameter "new-password" is required!', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test update user password when new password is too short
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenPasswordIsTooShort(): void
    {
        $this->client->request('PATCH', '/api/user/update/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'new-password' => '1'
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Parameter "new-password" must be between 8 and 128 characters long!', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test update user password when new password is too long
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenPasswordIsTooLong(): void
    {
        $this->client->request('PATCH', '/api/user/update/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'new-password' => ByteString::fromRandom(130)
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Parameter "new-password" must be between 8 and 128 characters long!', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test update user password when new password is valid
     *
     * @return void
     */
    public function testUpdateUserPasswordWhenNewPasswordIsValid(): void
    {
        $this->client->request('PATCH', '/api/user/update/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN'],
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateJwtToken()
        ], json_encode([
            'new-password' => 'testtest'
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Password updated successfully!', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }
}
