<?php

namespace Amp\SSH\Encryption\CipherMode;

interface CipherMode {
    public function getCurrentIV(): string;

    public function updateIV(string $payload): void;
}
