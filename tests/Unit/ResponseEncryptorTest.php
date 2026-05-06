<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zobayer\Encapsula\Exceptions\EncryptionException;
use Zobayer\Encapsula\Services\ResponseEncryptor;

class ResponseEncryptorTest extends TestCase
{
    private string $validKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validKey = base64_encode(random_bytes(32));
    }

    public function test_encrypt_returns_required_fields(): void
    {
        $encryptor = new ResponseEncryptor($this->validKey);
        $result = $encryptor->encrypt('{"name":"Ahmed"}');

        $this->assertArrayHasKey('payload', $result);
        $this->assertArrayHasKey('iv', $result);
        $this->assertArrayHasKey('tag', $result);
        $this->assertArrayHasKey('alg', $result);
        $this->assertNotEmpty($result['payload']);
        $this->assertNotEmpty($result['iv']);
        $this->assertNotEmpty($result['tag']);
    }

    public function test_encrypt_decrypt_roundtrip(): void
    {
        $encryptor = new ResponseEncryptor($this->validKey);
        $plaintext = '{"users":[{"id":1,"name":"Ahmed"}]}';

        $encrypted = $encryptor->encrypt($plaintext);
        $decrypted = $encryptor->decrypt($encrypted['payload'], $encrypted['iv'], $encrypted['tag']);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_different_ivs_per_encryption(): void
    {
        $encryptor = new ResponseEncryptor($this->validKey);
        $plaintext = '{"data":"test"}';

        $first = $encryptor->encrypt($plaintext);
        $second = $encryptor->encrypt($plaintext);

        $this->assertNotSame($first['iv'], $second['iv']);
        $this->assertNotSame($first['payload'], $second['payload']);
    }

    public function test_invalid_key_throws_exception(): void
    {
        $this->expectException(EncryptionException::class);
        $this->expectExceptionMessage('Invalid encryption key');

        new ResponseEncryptor('too-short');
    }

    public function test_empty_key_throws_on_encrypt(): void
    {
        $encryptor = new ResponseEncryptor('');

        $this->expectException(EncryptionException::class);
        $this->expectExceptionMessage('Invalid encryption key');

        $encryptor->encrypt('test');
    }

    public function test_tampered_payload_throws_exception(): void
    {
        $encryptor = new ResponseEncryptor($this->validKey);
        $encrypted = $encryptor->encrypt('{"data":"test"}');

        $this->expectException(EncryptionException::class);
        $this->expectExceptionMessage('decryption failed');

        $encryptor->decrypt('dGFtcGVyZWQ=', $encrypted['iv'], $encrypted['tag']);
    }

    public function test_tampered_tag_throws_exception(): void
    {
        $encryptor = new ResponseEncryptor($this->validKey);
        $encrypted = $encryptor->encrypt('{"data":"test"}');

        $this->expectException(EncryptionException::class);

        $encryptor->decrypt($encrypted['payload'], $encrypted['iv'], base64_encode('badtag1234567890'));
    }

    public function test_wrong_key_cannot_decrypt(): void
    {
        $encryptor1 = new ResponseEncryptor($this->validKey);
        $encrypted = $encryptor1->encrypt('{"secret":"data"}');

        $otherKey = base64_encode(random_bytes(32));
        $encryptor2 = new ResponseEncryptor($otherKey);

        $this->expectException(EncryptionException::class);

        $encryptor2->decrypt($encrypted['payload'], $encrypted['iv'], $encrypted['tag']);
    }

    public function test_algorithm_field_value(): void
    {
        $encryptor = new ResponseEncryptor($this->validKey);
        $result = $encryptor->encrypt('test');

        $this->assertSame('AES-256-GCM', $result['alg']);
    }

    public function test_large_payload_roundtrip(): void
    {
        $encryptor = new ResponseEncryptor($this->validKey);
        $plaintext = json_encode(array_fill(0, 1000, ['id' => 1, 'name' => 'test', 'email' => 'test@example.com']));

        $encrypted = $encryptor->encrypt($plaintext);
        $decrypted = $encryptor->decrypt($encrypted['payload'], $encrypted['iv'], $encrypted['tag']);

        $this->assertSame($plaintext, $decrypted);
    }
}
