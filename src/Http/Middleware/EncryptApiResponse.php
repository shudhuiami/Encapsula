<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zobayer\Encapsula\Contracts\Encryptor;

/**
 * Middleware that encrypts JSON API responses using the configured Encryptor.
 *
 * Safely skips non-JSON responses, redirects, streamed/binary responses,
 * empty responses, and routes matching the configured exclusion patterns.
 */
class EncryptApiResponse
{
    public function __construct(
        protected Encryptor $encryptor
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        /** @var Response|JsonResponse|StreamedResponse|BinaryFileResponse $response */
        $response = $next($request);

        if (! $this->shouldEncrypt($request, $response)) {
            return $response;
        }

        return $this->encryptResponse($response);
    }

    protected function shouldEncrypt(Request $request, mixed $response): bool
    {
        if (! config('encapsula.enabled', true)) {
            return false;
        }

        if (! $this->hasEncryptionKey($request)) {
            return false;
        }

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        if (! $response instanceof Response && ! $response instanceof JsonResponse) {
            return false;
        }

        if ($response->isRedirection() || $response->isEmpty()) {
            return false;
        }

        if (! $this->isJsonResponse($response)) {
            return false;
        }

        if ($this->isExcludedRoute($request)) {
            return false;
        }

        return true;
    }

    protected function hasEncryptionKey(Request $request): bool
    {
        /** @var string $mode */
        $mode = (string) config('encapsula.key_mode', 'static');

        if ($mode === 'session') {
            if (! method_exists($request, 'hasSession') || ! $request->hasSession()) {
                return false;
            }

            /** @var array<string, mixed> $handshake */
            $handshake = (array) config('encapsula.handshake', []);
            $sessionKeyName = (string) ($handshake['session_key'] ?? 'encapsula.session_key');

            return (string) $request->session()->get($sessionKeyName, '') !== '';
        }

        return (string) config('encapsula.key', '') !== '';
    }

    protected function isJsonResponse(Response|JsonResponse $response): bool
    {
        if ($response instanceof JsonResponse) {
            return true;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'application/json');
    }

    protected function isExcludedRoute(Request $request): bool
    {
        /** @var array<int, string> $excludePatterns */
        $excludePatterns = config('encapsula.exclude', []);

        if (empty($excludePatterns)) {
            return false;
        }

        $routeName = Route::currentRouteName();

        if ($routeName === null) {
            return false;
        }

        foreach ($excludePatterns as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    protected function encryptResponse(Response|JsonResponse $response): JsonResponse
    {
        $content = $response->getContent();

        if ($content === false || $content === '') {
            return new JsonResponse($response->getContent(), $response->getStatusCode(), $response->headers->all());
        }

        $encrypted = $this->encryptor->encrypt($content);

        /** @var array<string, string> $envelopeConfig */
        $envelopeConfig = config('encapsula.envelope', []);

        $envelope = [
            ($envelopeConfig['encrypted_field'] ?? 'encrypted') => true,
            ($envelopeConfig['payload_field'] ?? 'payload') => $encrypted['payload'],
            ($envelopeConfig['iv_field'] ?? 'iv') => $encrypted['iv'],
            ($envelopeConfig['tag_field'] ?? 'tag') => $encrypted['tag'],
            ($envelopeConfig['algorithm_field'] ?? 'alg') => $encrypted['alg'],
        ];

        return new JsonResponse($envelope, $response->getStatusCode(), $response->headers->all());
    }
}
