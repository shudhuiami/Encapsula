<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Zobayer\Encapsula\EncapsulaServiceProvider;

final class SessionHandshakeTest extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [EncapsulaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('encapsula.enabled', true);
        $app['config']->set('encapsula.key', '');
        $app['config']->set('encapsula.key_mode', 'session');
        $app['config']->set('encapsula.handshake.enabled', true);
        $app['config']->set('encapsula.handshake.middleware', ['web']);
        $app['config']->set('encapsula.handshake.path', '/encapsula/handshake');
    }

    public function test_session_mode_encrypts_after_handshake(): void
    {
        Route::middleware('encapsula.encrypt')->get('/session-test', function () {
            return response()->json(['ok' => true]);
        });

        // No session key yet => pass through.
        $this->getJson('/session-test')->assertOk()->assertJson(['ok' => true]);

        $client = $this->withSession([]);

        $clientPublicKey = $this->generateClientPublicSpkiDerBase64();

        $client->postJson('/encapsula/handshake', [
            'client_public_key' => $clientPublicKey,
        ])->assertOk()->assertJsonStructure(['server_public_key', 'salt', 'kdf', 'curve', 'key_length']);

        $client->getJson('/session-test')
            ->assertOk()
            ->assertJsonStructure(['encrypted', 'payload', 'iv', 'tag', 'alg'])
            ->assertJson(['encrypted' => true]);
    }

    private function generateClientPublicSpkiDerBase64(): string
    {
        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        if ($privateKey === false) {
            $this->fail('Failed to generate EC keypair for client.');
        }

        $details = openssl_pkey_get_details($privateKey);

        if (! is_array($details) || ! isset($details['key']) || ! is_string($details['key'])) {
            $this->fail('Failed to export client public key.');
        }

        $pem = str_replace(["\r", "\n"], '', $details['key']);
        $pem = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'], '', $pem);

        $der = base64_decode($pem, true);

        if ($der === false || $der === '') {
            $this->fail('Failed to encode client public key.');
        }

        return base64_encode($der);
    }
}

