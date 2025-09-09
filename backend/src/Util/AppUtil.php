<?php

namespace App\Util;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AppUtil
 *
 * The basic utilities class for the application
 *
 * @package App\Util
 */
class AppUtil
{
    private RequestStack $requestStack;
    private KernelInterface $kernelInterface;

    public function __construct(RequestStack $requestStack, KernelInterface $kernelInterface)
    {
        $this->requestStack = $requestStack;
        $this->kernelInterface = $kernelInterface;
    }

    /**
     * Get the request uri
     *
     * @return string|null The request uri
     */
    public function getRequestUri(): ?string
    {
        // get current request
        $request = $this->requestStack->getCurrentRequest();

        // if no request, return null
        if ($request === null) {
            return null;
        }

        // get request uri
        return $request->getRequestUri();
    }

    /**
     * Get the request method
     *
     * @return string|null The request method
     */
    public function getRequestMethod(): ?string
    {
        // get current request
        $request = $this->requestStack->getCurrentRequest();

        // if no request, return null
        if ($request === null) {
            return null;
        }

        // get request method
        return $request->getMethod();
    }

    /**
     * Get the application root directory
     *
     * @return string The application root directory
     */
    public function getAppRootDir(): string
    {
        return $this->kernelInterface->getProjectDir();
    }

    /**
     * Get config from yaml file
     *
     * @param string $configFile The config file name
     *
     * @return mixed The config data
     */
    public function getYamlConfig(string $configFile): mixed
    {
        return Yaml::parseFile($this->getAppRootDir() . '/config/' . $configFile);
    }

    /**
     * Get the environment variable value
     *
     * @param string $key The environment variable key
     *
     * @return string The environment variable value
     */
    public function getEnvValue(string $key): string
    {
        return $_ENV[$key];
    }

    /**
     * Check if the database logging is enabled
     *
     * @return bool True if the database logging is enabled, false otherwise
     */
    public function isDatabaseLoggingEnabled(): bool
    {
        return $this->getEnvValue('DATABASE_LOGGING') === 'true';
    }

    /**
     * Check if the application is in development mode
     *
     * @return bool True if the application is in development mode, false otherwise
     */
    public function isDevMode(): bool
    {
        $envName = $this->getEnvValue('APP_ENV');

        if ($envName == 'dev' || $envName == 'test') {
            return true;
        }

        return false;
    }

    /**
     * Check if the application is in maintenance mode
     *
     * @return bool True if the application is in maintenance mode, false otherwise
     */
    public function isMaintenance(): bool
    {
        return $this->getEnvValue('MAINTENANCE_MODE') === 'true';
    }

    /**
     * Check if the SSL only is enabled
     *
     * @return bool True if the SSL only is enabled, false otherwise
     */
    public function isSSLOnly(): bool
    {
        return $this->getEnvValue('SSL_ONLY') === 'true';
    }

    /**
     * Check if the request is SSL
     *
     * @return bool True if the request is SSL, false otherwise
     */
    public function isSsl(): bool
    {
        // check if HTTPS header is set and its value is either 1 or 'on'
        return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) === 'on');
    }
}
