<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Console;

use Illuminate\Console\Command;
use RuntimeException;

final class EncapsulaSetupCommand extends Command
{
    protected $signature = 'encapsula:setup
                            {--mode=static : Key mode (static|session)}
                            {--handshake : Enable session handshake (session mode)}
                            {--write : Write values into the .env file}
                            {--force : Overwrite existing keys in .env when --write is used}';

    protected $description = 'Scaffold Encapsula environment variables';

    public function handle(): int
    {
        $mode = strtolower((string) $this->option('mode'));
        if (! in_array($mode, ['static', 'session'], true)) {
            $this->error('Invalid --mode. Use "static" or "session".');

            return self::INVALID;
        }

        $enableHandshake = (bool) $this->option('handshake');

        $lines = $this->buildEnvLines($mode, $enableHandshake);

        $this->line('Add these values to your .env:');
        $this->newLine();
        foreach ($lines as $line) {
            $this->line($line);
        }
        $this->newLine();

        if (! (bool) $this->option('write')) {
            return self::SUCCESS;
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $this->error('No .env file found at: '.$envPath);

            return self::FAILURE;
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            $this->error('Failed to read .env file.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        try {
            $updated = $this->upsertEnv($contents, $lines, $force);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (file_put_contents($envPath, $updated) === false) {
            $this->error('Failed to write .env file.');

            return self::FAILURE;
        }

        $this->info('Updated .env successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function buildEnvLines(string $mode, bool $enableHandshake): array
    {
        $lines = [
            'ENCAPSULA_ENABLED=true',
            'ENCAPSULA_KEY_MODE='.$mode,
            'ENCAPSULA_ALGORITHM=aes-256-gcm',
        ];

        if ($mode === 'static') {
            $lines[] = 'ENCAPSULA_KEY='.base64_encode(random_bytes(32));
        } else {
            // In session mode the static key is unused; keep it empty.
            $lines[] = 'ENCAPSULA_KEY=';
            $lines[] = 'ENCAPSULA_HANDSHAKE_ENABLED='.($enableHandshake ? 'true' : 'false');
            $lines[] = 'ENCAPSULA_HANDSHAKE_PATH=/encapsula/handshake';
        }

        return $this->dedupeKeys($lines);
    }

    /**
     * @param array<int, string> $lines
     * @return array<int, string>
     */
    private function dedupeKeys(array $lines): array
    {
        $out = [];
        $seen = [];

        foreach ($lines as $line) {
            $key = strstr($line, '=', true);
            if ($key === false) {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $line;
        }

        return $out;
    }

    /**
     * @param array<int, string> $lines
     */
    private function upsertEnv(string $contents, array $lines, bool $force): string
    {
        $eol = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $contents = rtrim($contents, "\r\n");

        foreach ($lines as $line) {
            $key = strstr($line, '=', true);
            if ($key === false || $key === '') {
                continue;
            }

            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $exists = (bool) preg_match($pattern, $contents);

            if ($exists && ! $force) {
                continue;
            }

            if ($exists) {
                $contents = (string) preg_replace($pattern, $line, $contents);
                continue;
            }

            $contents .= $eol.$line;
        }

        return $contents.$eol;
    }
}

