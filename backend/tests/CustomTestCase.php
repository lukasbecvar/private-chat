<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * Class CustomTestCase
 *
 * Custom test case for testing controllers
 *
 * @package App\Tests
 */
class CustomTestCase extends WebTestCase
{
    /**
     * Generate user token for testing purposes
     *
     * @return string The generated JWT token
     */
    public function generateJwtToken(): string
    {
        // init test user (created with datafixtures)
        $fakeUser = new User();
        $fakeUser->setEmail('test@test.test');

        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        // generate JWT token
        return $jwtManager->create($fakeUser);
    }

    /**
     * Get response data from response content
     *
     * @param string|false $responseContent The response content
     *
     * @return array<mixed> The response data
     */
    public function getResponseData(string|false $responseContent): array
    {
        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        // decode response data
        return json_decode($responseContent, true);
    }
}
