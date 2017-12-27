<?php

declare(strict_types=1);

namespace Amp\SSH\Encryption\CipherMode;

interface CipherMode
{
    public function getCurrentIV(): string;

    public function updateIV(string $payload): void;
}
