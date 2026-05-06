<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Contracts;

use Zobayer\Encapsula\Exceptions\EncryptionException;

/**
 * Contract for response payload encryption.
 */
interface Encryptor
{
    /**
     * Encrypt a plaintext string.
     *
     * @param  string  $plaintext  The data to encrypt.
     * @return array{payload: string, iv: string, tag: string, alg: string}
     *
     * @throws EncryptionException
     */
    public function encrypt(string $plaintext): array;

    /**
     * Decrypt an encrypted payload.
     *
     * @param  string  $payload  Base64-encoded ciphertext.
     * @param  string  $iv  Base64-encoded initialization vector.
     * @param  string  $tag  Base64-encoded authentication tag.
     * @return string The decrypted plaintext.
     *
     * @throws EncryptionException
     */
    public function decrypt(string $payload, string $iv, string $tag): string;
}
