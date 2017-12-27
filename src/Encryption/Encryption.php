<?php

namespace Amp\SSH\Encryption;

interface Encryption
{
    public function getBlockSize(): int;

    public function crypt(string $payload): string;

    public function getName(): string;

    public function resetEncrypt(string $key, string $initIv): void;
}
