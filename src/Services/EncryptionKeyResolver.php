<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Services;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;

final class EncryptionKeyResolver
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly ?Session $session,
    ) {}

    public function getBase64Key(): string
    {
        /** @var string $mode */
        $mode = (string) $this->config->get('encapsula.key_mode', 'static');

        if ($mode === 'session') {
            if ($this->session === null) {
                return '';
            }

            /** @var array<string, mixed> $handshake */
            $handshake = (array) $this->config->get('encapsula.handshake', []);
            $sessionKeyName = (string) ($handshake['session_key'] ?? 'encapsula.session_key');

            /** @var mixed $key */
            $key = $this->session->get($sessionKeyName);

            return is_string($key) ? $key : '';
        }

        /** @var string $key */
        $key = (string) $this->config->get('encapsula.key', '');

        return $key;
    }
}

