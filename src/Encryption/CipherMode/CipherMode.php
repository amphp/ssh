<?php

namespace Amp\Ssh\Encryption\CipherMode;

interface CipherMode {
    public function getCurrentIV(): string;

    public function updateIV(string $payload);
}
