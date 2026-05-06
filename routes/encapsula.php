<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zobayer\Encapsula\Http\Controllers\HandshakeController;

/** @var array<string, mixed> $handshake */
$handshake = (array) config('encapsula.handshake', []);

/** @var bool $enabled */
$enabled = (bool) ($handshake['enabled'] ?? false);

if ($enabled) {
    /** @var string $path */
    $path = (string) ($handshake['path'] ?? '/encapsula/handshake');

    /** @var array<int, string> $middleware */
    $middleware = (array) ($handshake['middleware'] ?? ['web']);

    Route::middleware($middleware)->post($path, HandshakeController::class)->name('encapsula.handshake');
}

