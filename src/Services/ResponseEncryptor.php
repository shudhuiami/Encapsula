<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Services;

use Zobayer\Encapsula\Contracts\Encryptor;
use Zobayer\Encapsula\Exceptions\EncryptionException;

/**
 * Encrypts and decrypts response payloads using AES-256-GCM.
 *
 * Uses OpenSSL for authenticated encryption with a random IV per operation
 * and a 128-bit authentication tag for tamper detection.
 */
class ResponseEncryptor implements Encryptor
{
    protected string $key;

    protected string $algorithm;

    public function __construct(string $key, string $algorithm = 'aes-256-gcm')
    {
        if ($key === '') {
            $this->key = '';
            $this->algorithm = $algorithm;

            return;
        }

        $decoded = base64_decode($key, true);

        if ($decoded === false || strlen($decoded) !== 32) {
            throw EncryptionException::invalidKey();
        }

        $this->key = $decoded;
        $this->algorithm = $algorithm;
    }

    public function encrypt(string $plaintext): array
    {
        if ($this->key === '') {
            throw EncryptionException::invalidKey();
        }

        $ivLength = openssl_cipher_iv_length($this->algorithm);

        if ($ivLength === false) {
            throw EncryptionException::encryptionFailed('Unsupported algorithm: '.$this->algorithm);
        }

        $iv = random_bytes($ivLength);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->algorithm,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw EncryptionException::encryptionFailed(openssl_error_string() ?: '');
        }

        return [
            'payload' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'alg' => strtoupper(str_replace('-', '-', $this->algorithm)),
        ];
    }

    public function decrypt(string $payload, string $iv, string $tag): string
    {
        $ciphertext = base64_decode($payload, true);
        $decodedIv = base64_decode($iv, true);
        $decodedTag = base64_decode($tag, true);

        if ($ciphertext === false || $decodedIv === false || $decodedTag === false) {
            throw EncryptionException::decryptionFailed('Invalid base64 encoding.');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->algorithm,
            $this->key,
            OPENSSL_RAW_DATA,
            $decodedIv,
            $decodedTag,
        );

        if ($plaintext === false) {
            throw EncryptionException::decryptionFailed(openssl_error_string() ?: 'Authentication tag verification failed.');
        }

        return $plaintext;
    }
}
