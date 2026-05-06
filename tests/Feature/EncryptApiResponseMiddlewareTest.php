<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Zobayer\Encapsula\Contracts\Encryptor;
use Zobayer\Encapsula\Tests\TestCase;

class EncryptApiResponseMiddlewareTest extends TestCase
{
    public function test_encrypts_json_response(): void
    {
        Route::middleware('encapsula.encrypt')->get('/test-encrypt', function () {
            return response()->json(['name' => 'Ahmed', 'email' => 'ahmed@example.com']);
        });

        $response = $this->getJson('/test-encrypt');

        $response->assertOk();
        $response->assertJsonStructure(['encrypted', 'payload', 'iv', 'tag', 'alg']);
        $response->assertJson(['encrypted' => true]);
    }

    public function test_encrypted_response_can_be_decrypted(): void
    {
        Route::middleware('encapsula.encrypt')->get('/test-decrypt', function () {
            return response()->json(['secret' => 'data']);
        });

        $response = $this->getJson('/test-decrypt');
        $body = $response->json();

        $encryptor = $this->app->make(Encryptor::class);
        $decrypted = $encryptor->decrypt($body['payload'], $body['iv'], $body['tag']);
        $data = json_decode($decrypted, true);

        $this->assertSame(['secret' => 'data'], $data);
    }

    public function test_disabled_mode_passes_through(): void
    {
        $this->app['config']->set('encapsula.enabled', false);

        Route::middleware('encapsula.encrypt')->get('/test-disabled', function () {
            return response()->json(['name' => 'Ahmed']);
        });

        $response = $this->getJson('/test-disabled');

        $response->assertOk();
        $response->assertJson(['name' => 'Ahmed']);
        $response->assertJsonMissing(['encrypted' => true]);
    }

    public function test_no_key_passes_through(): void
    {
        $this->app['config']->set('encapsula.key', '');

        Route::middleware('encapsula.encrypt')->get('/test-no-key', function () {
            return response()->json(['name' => 'Ahmed']);
        });

        $response = $this->getJson('/test-no-key');

        $response->assertOk();
        $response->assertJson(['name' => 'Ahmed']);
    }

    public function test_excluded_route_skips_encryption(): void
    {
        $this->app['config']->set('encapsula.exclude', ['health']);

        Route::middleware('encapsula.encrypt')->get('/health', function () {
            return response()->json(['status' => 'ok']);
        })->name('health');

        $response = $this->getJson('/health');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
        $response->assertJsonMissing(['encrypted' => true]);
    }

    public function test_excluded_route_wildcard(): void
    {
        $this->app['config']->set('encapsula.exclude', ['auth.*']);

        Route::middleware('encapsula.encrypt')->get('/login', function () {
            return response()->json(['token' => 'abc']);
        })->name('auth.login');

        $response = $this->getJson('/login');

        $response->assertOk();
        $response->assertJson(['token' => 'abc']);
    }

    public function test_non_json_response_skipped(): void
    {
        Route::middleware('encapsula.encrypt')->get('/test-html', function () {
            return new Response('<h1>Hello</h1>', 200, ['Content-Type' => 'text/html']);
        });

        $response = $this->get('/test-html');

        $response->assertOk();
        $this->assertStringContains('<h1>Hello</h1>', $response->getContent());
    }

    public function test_redirect_response_skipped(): void
    {
        Route::middleware('encapsula.encrypt')->get('/test-redirect', function () {
            return redirect('/other');
        });

        $response = $this->get('/test-redirect');

        $response->assertRedirect('/other');
    }

    public function test_empty_response_skipped(): void
    {
        Route::middleware('encapsula.encrypt')->get('/test-empty', function () {
            return response('', 204);
        });

        $response = $this->get('/test-empty');

        $response->assertNoContent();
    }

    public function test_validation_error_response_encrypted(): void
    {
        Route::middleware('encapsula.encrypt')->post('/test-validation', function (Request $request) {
            $request->validate(['email' => 'required|email']);

            return response()->json(['ok' => true]);
        });

        $response = $this->postJson('/test-validation', ['email' => 'bad']);

        $response->assertStatus(422);
        $response->assertJsonStructure(['encrypted', 'payload', 'iv', 'tag', 'alg']);
    }

    public function test_preserves_status_code(): void
    {
        Route::middleware('encapsula.encrypt')->get('/test-created', function () {
            return response()->json(['id' => 1], 201);
        });

        $response = $this->getJson('/test-created');

        $response->assertStatus(201);
        $response->assertJson(['encrypted' => true]);
    }

    public function test_envelope_field_names_configurable(): void
    {
        $this->app['config']->set('encapsula.envelope', [
            'encrypted_field' => 'enc',
            'payload_field' => 'data',
            'iv_field' => 'nonce',
            'tag_field' => 'auth',
            'algorithm_field' => 'cipher',
        ]);

        Route::middleware('encapsula.encrypt')->get('/test-custom-envelope', function () {
            return response()->json(['name' => 'Ahmed']);
        });

        $response = $this->getJson('/test-custom-envelope');

        $response->assertOk();
        $response->assertJsonStructure(['enc', 'data', 'nonce', 'auth', 'cipher']);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
