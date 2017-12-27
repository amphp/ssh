<?php

namespace Amp\SSH\Encryption;

class None implements Encryption, Decryption
{
    public function getBlockSize(): int
    {
        return 8;
    }

    public function crypt(string $payload): string
    {
        return $payload;
    }

    public function getKeySize(): int
    {
        return 0;
    }

    public function decrypt(string $payload): string
    {
        return $payload;
    }

    public function getName(): string
    {
        return 'none';
    }

    public function resetDecrypt(string $key, string $initIv): void
    {
    }

    public function resetEncrypt(string $key, string $initIv): void
    {
    }
}
