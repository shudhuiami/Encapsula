<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Exceptions;

use RuntimeException;

/**
 * Thrown when encryption or decryption fails.
 */
class EncryptionException extends RuntimeException
{
    public static function encryptionFailed(string $reason = ''): self
    {
        $message = 'Response encryption failed.';
        if ($reason !== '') {
            $message .= ' '.$reason;
        }

        return new self($message);
    }

    public static function decryptionFailed(string $reason = ''): self
    {
        $message = 'Response decryption failed.';
        if ($reason !== '') {
            $message .= ' '.$reason;
        }

        return new self($message);
    }

    public static function invalidKey(): self
    {
        return new self('Invalid encryption key. Key must be 32 bytes (256 bits), base64-encoded.');
    }
}
