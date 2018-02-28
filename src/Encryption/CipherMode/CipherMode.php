<?php

namespace Amp\Ssh\Encryption\CipherMode;

/**
 * @internal
 */
interface CipherMode {
    public function getCurrentIV(): string;

    public function updateIV(string $payload);
}
