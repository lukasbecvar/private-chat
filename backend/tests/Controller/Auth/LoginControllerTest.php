<?php

namespace App\Tests\Auth;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LoginControllerTest
 *
 * Test cases for login API endpoint
 *
 * Note: this controller cannot be in the Controller namespace because it is fully managed by the JWT Auth bundle
 *
 * @package App\Tests\Auth
 */
class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test user login when request method is invalid
     *
     * @return void
     */
    public function testUserLoginWhenRequestMethodIsNotValid(): void
    {
        $this->client->request('GET', '/api/auth/login');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test user login when username is blank
     *
     * @return void
     */
    public function testUserLoginWhenUsernameIsBlank(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN']
        ], json_encode([
            'username' => '',
            'password' => 'test'
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertEquals('The key "username" must be a non-empty string.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test user login when password is blank
     *
     * @return void
     */
    public function testUserLoginWhenPasswordIsBlank(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN']
        ], json_encode([
            'username' => 'test',
            'password' => ''
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $responseData['status']);
        $this->assertEquals('The key "password" must be a non-empty string.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Test user login when user is banned
     *
     * @return void
     */
    public function testUserLoginWhenUserIsBanned(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'testing_user_10',
            'password' => 'password'
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Account is not active, account status is: banned', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * Test user login when credentials are invalid
     *
     * @return void
     */
    public function testUserLoginWhenCredentialsAreInvalid(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN']
        ], json_encode([
            'username' => 'fugazi@fufu.xyz',
            'password' => 'invalid-password'
        ]) ?: null);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('Invalid credentials.', $responseData['message']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Test user login when credentials are valid
     *
     * @return void
     */
    public function testUserLoginWhenCredentialsAreValid(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_TOKEN' => $_ENV['API_TOKEN']
        ], json_encode([
            'username' => 'test',
            'password' => 'test'
        ]) ?: null);

        $response = $this->client->getResponse();

        // assert status
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_NO_CONTENT);

        // get jwt token from cookie
        $cookies = $response->headers->getCookies();
        $bearerCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'BEARER') {
                $bearerCookie = $cookie;
                break;
            }
        }

        // assert cookie is set
        $this->assertNotNull($bearerCookie, 'BEARER cookie not set in response');
        $this->assertNotEmpty($bearerCookie->getValue(), 'BEARER cookie value is empty');
    }
}
