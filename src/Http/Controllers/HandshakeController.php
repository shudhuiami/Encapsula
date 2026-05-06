<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class HandshakeController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->hasSession()) {
            return new JsonResponse([
                'message' => 'Encapsula handshake requires session middleware.',
            ], 500);
        }

        /** @var array{client_public_key?: mixed} $data */
        $data = $request->validate([
            'client_public_key' => ['required', 'string'],
        ]);

        $clientPublicDer = base64_decode((string) $data['client_public_key'], true);

        if ($clientPublicDer === false || $clientPublicDer === '') {
            return new JsonResponse([
                'message' => 'Invalid client_public_key (expected base64 SPKI DER).',
            ], 422);
        }

        $clientPublicKey = $this->publicKeyFromSpkiDer($clientPublicDer);
        $serverKeyPair = $this->generateEcKeyPair();

        $sharedSecret = openssl_pkey_derive($clientPublicKey, $serverKeyPair, 32);

        if (! is_string($sharedSecret) || $sharedSecret === '') {
            throw new RuntimeException('Failed to derive shared secret.');
        }

        $salt = random_bytes(16);
        $info = 'encapsula-session-key-v1';

        $sessionKey = hash_hkdf('sha256', $sharedSecret, 32, $info, $salt);

        if (! is_string($sessionKey) || strlen($sessionKey) !== 32) {
            throw new RuntimeException('Failed to derive session key.');
        }

        /** @var array<string, mixed> $handshake */
        $handshake = (array) config('encapsula.handshake', []);
        $sessionKeyName = (string) ($handshake['session_key'] ?? 'encapsula.session_key');

        $request->session()->put($sessionKeyName, base64_encode($sessionKey));

        $serverPublicDer = $this->spkiDerFromPrivateKey($serverKeyPair);

        return new JsonResponse([
            'server_public_key' => base64_encode($serverPublicDer),
            'salt' => base64_encode($salt),
            'kdf' => 'HKDF-SHA256',
            'curve' => 'P-256',
            'key_length' => 32,
        ]);
    }

    /**
     * @return OpenSSLAsymmetricKey
     */
    private function generateEcKeyPair()
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        if ($key === false) {
            throw new RuntimeException('Failed to generate EC key pair.');
        }

        return $key;
    }

    /**
     * @return OpenSSLAsymmetricKey
     */
    private function publicKeyFromSpkiDer(string $der)
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n".
            chunk_split(base64_encode($der), 64, "\n").
            "-----END PUBLIC KEY-----\n";

        $key = openssl_pkey_get_public($pem);

        if ($key === false) {
            throw new RuntimeException('Invalid client public key.');
        }

        return $key;
    }

    private function spkiDerFromPrivateKey($privateKey): string
    {
        $details = openssl_pkey_get_details($privateKey);

        if (! is_array($details) || ! isset($details['key']) || ! is_string($details['key'])) {
            throw new RuntimeException('Failed to export server public key.');
        }

        $pem = $details['key'];
        $pem = str_replace(["\r", "\n"], '', $pem);
        $pem = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'], '', $pem);

        $der = base64_decode($pem, true);

        if ($der === false || $der === '') {
            throw new RuntimeException('Failed to encode server public key.');
        }

        return $der;
    }
}

