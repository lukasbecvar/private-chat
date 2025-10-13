<?php

namespace App\Util;

use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EncryptionUtil
 *
 * Util for encryption/decryption with AES
 *
 * @package App\Util
 */
class EncryptionUtil
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Encrypt string using AES encryption
     *
     * @param string $plainText The plain text to encrypt
     * @param string $method The encryption method (default: AES-128-CBC)
     * @param string|null $key The encryption key (default: APP_SECRET)
     *
     * @return string The base64-encoded encrypted string
     */
    public function encryptAes(string $plainText, string $method = 'AES-128-CBC', ?string $key = null): string
    {
        // default encryption key
        if ($key === null) {
            $key = $this->appUtil->getEnvValue('APP_SECRET');
        }

        // derive fixed-size key using PBKDF2 with SHA-256
        $derivedKey = hash_pbkdf2('sha256', $key, '', 10000, 32);

        // generate random Initialization Vector (IV) for added security
        $iv = openssl_random_pseudo_bytes(16);

        // encrypt plain text using AES encryption with the derived key and IV
        $encryptedData = openssl_encrypt($plainText, $method, $derivedKey, 0, $iv);

        // IV and encrypted data, then base64 encode the result
        $result = $iv . $encryptedData;

        return base64_encode($result);
    }

    /**
     * Decrypt AES-encrypted string
     *
     * @param string $encryptedData The base64-encoded encrypted string
     * @param string $method The encryption method (default: AES-128-CBC)
     * @param string|null $key The encryption key (default: APP_SECRET)
     *
     * @return string|null The decrypted string or null on error
     */
    public function decryptAes(string $encryptedData, string $method = 'AES-128-CBC', ?string $key = null): ?string
    {
        // default encryption key
        if ($key === null) {
            $key = $this->appUtil->getEnvValue('APP_SECRET');
        }

        // derive fixed-size key using PBKDF2 with SHA-256
        $derivedKey = hash_pbkdf2('sha256', $key, '', 10000, 32);

        // decode base64-encoded encrypted data
        $decodedData = base64_decode($encryptedData);

        // extract Initialization Vector (IV) from the decoded data
        $iv = substr($decodedData, 0, 16);

        // extract encrypted data (remaining bytes) from the decoded data
        $encryptedData = substr($decodedData, 16);

        // decrypt data using AES decryption with the derived key and IV
        $decryptedData = openssl_decrypt($encryptedData, $method, $derivedKey, 0, $iv);

        // check if decryption was successful
        if ($decryptedData === false) {
            $this->errorManager->handleError(
                message: 'Decryption failed',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $decryptedData;
    }
}
