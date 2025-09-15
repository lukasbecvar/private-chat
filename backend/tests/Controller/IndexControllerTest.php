<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class IndexControllerTest
 *
 * Test cases for index controller
 *
 * @package App\Tests\Controller
 */
class IndexControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load index route
     *
     * @return void
     */
    public function testLoadIndexRoute(): void
    {
        $this->client->request('GET', '/');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('success', $responseData['status']);
        $this->assertSame('private-chat api is running!', $responseData['message']);
        $this->assertSame($_ENV['APP_VERSION'], $responseData['version']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }
}
