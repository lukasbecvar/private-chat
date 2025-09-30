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
     * Encrypt plaintext string to AES
     *
     * @param string $plaintext The data to encrypt
     * @param int $iterations Number of PBKDF2 iterations (default 300000)
     *
     * @return string Base64 encoded JSON containing salt, iv, tag and ciphertext returns empty string on failure
     */
    public function encryptString(string $plaintext, int $iterations = 300000): string
    {
        // encryption password
        $password = $this->appUtil->getEncryptionPassword();

        // validate iterations
        if ($iterations <= 0) {
            $this->errorManager->handleError(
                'Invalid number of PBKDF2 iterations',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Encryption error'
            );
        }

        // parameters
        $saltLen = 16; // 16 bytes = 128 bits salt for PBKDF2
        $ivLen   = 12; // 12 bytes IV = recommended size for GCM mode
        $keyLen  = 32; // 32 bytes = 256-bit AES key
        $cipher  = 'aes-256-gcm';

        // generate random salt and IV for each encryption
        $salt = random_bytes($saltLen);
        $iv   = random_bytes($ivLen);

        // derive encryption key from password, salt and iterations using PBKDF2-HMAC-SHA256
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, $keyLen, true);
        if (strlen($key) !== $keyLen) {
            $this->errorManager->handleError(
                'Key derivation failed',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Encryption error'
            );
        }

        // metadata protection (version + iterations) against modification
        $aad = json_encode(['v' => 1, 'iter' => $iterations], JSON_UNESCAPED_SLASHES);
        if ($aad === false) {
            $this->errorManager->handleError(
                'Metadata protection failed',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Encryption error'
            );
        }

        // auth tag will be filled by openssl_encrypt
        $tag = '';

        // encrypt plaintext
        $ciphertext = openssl_encrypt(
            $plaintext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        // validate encryption result
        if (!is_string($ciphertext) || strlen($tag) !== 16) {
            $this->errorManager->handleError(
                'Encryption failed',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Encryption error'
            );
        }

        // pack data into JSON and encode with Base64
        $data = [
            'v'    => 1,                          // version for future upgrades
            'salt' => base64_encode($salt),       // salt used for PBKDF2
            'iv'   => base64_encode($iv),         // IV used for GCM
            'tag'  => base64_encode($tag),        // authentication tag
            'ct'   => base64_encode($ciphertext), // ciphertext
            'iter' => $iterations,                // PBKDF2 iterations
        ];

        // encode data into JSON and encode with Base64
        $encryptedData = json_encode($data, JSON_UNESCAPED_SLASHES);
        if ($encryptedData === false) {
            $this->errorManager->handleError(
                'JSON encoding failed',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Encryption error'
            );
        }

        return base64_encode($encryptedData);
    }

    /**
     * Decrypt AES encrypted string
     *
     * @param string $b64json Base64 encoded JSON produced by encryptString()
     *
     * @return string Returns the decrypted plaintext string, or false on failure
     */
    public function decryptString(string $b64json): string
    {
        // decryption password
        $password = $this->appUtil->getEncryptionPassword();

        // decode base64 input
        $decoded = base64_decode($b64json, true);
        if ($decoded === false) {
            $this->errorManager->handleError(
                'Invalid base64 input',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Decryption error'
            );
        }

        // decode json
        $data = json_decode($decoded, true);
        if (!is_array($data) || !isset($data['salt'], $data['iv'], $data['tag'], $data['ct'], $data['iter'], $data['v'])) {
            $this->errorManager->handleError(
                'Invalid JSON input',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Decryption error'
            );
        }

        // decode and validate binary data
        $salt = base64_decode($data['salt'], true);
        $iv   = base64_decode($data['iv'], true);
        $tag  = base64_decode($data['tag'], true);
        $ct   = base64_decode($data['ct'], true);
        $iterations = (int)$data['iter'];

        // validate lengths of salt, IV and tag
        if (
            !is_string($salt) || strlen($salt) !== 16 ||
            !is_string($iv)   || strlen($iv) !== 12 ||
            !is_string($tag)  || strlen($tag) !== 16 ||
            !is_string($ct)   || $iterations <= 0
        ) {
            $this->errorManager->handleError(
                'Invalid encryption data',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Decryption error'
            );
        }

        // derive encryption key again using PBKDF2
        $keyLen = 32;
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, $keyLen, true);
        if (strlen($key) !== $keyLen) {
            $this->errorManager->handleError(
                'Key derivation failed',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Decryption error'
            );
        }

        // aad must be exactly the same as in encryption
        $aad = json_encode(['v' => $data['v'], 'iter' => $iterations], JSON_UNESCAPED_SLASHES);
        if ($aad === false) {
            $this->errorManager->handleError(
                'Metadata protection failed',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Decryption error'
            );
        }

        // decrypt ciphertext
        $cipher = 'aes-256-gcm';
        $plaintext = openssl_decrypt(
            $ct,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        // validate decryption result
        if (!is_string($plaintext)) {
            $this->errorManager->handleError(
                'Decryption failed',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Decryption error'
            );
        }

        // return decrypted plaintext
        return $plaintext;
    }
}
