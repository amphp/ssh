<?php

namespace Amp\SSH\Encryption;

use Amp\SSH\Encryption\CipherMode\CipherMode;
use Amp\SSH\Encryption\CipherMode\Ctr;

class Aes128Ctr extends OpenSSL
{
    public function getName(): string
    {
        return 'aes128-ctr';
    }

    public function getBlockSize(): int
    {
        return 16;
    }

    protected function getOpenSSLMethod(): string
    {
        return 'aes-128-ctr';
    }

    protected function createCipherMode(string $iv): CipherMode
    {
        return new Ctr($iv, $this->getOpenSSLMethod(), $this->getBlockSize());
    }

    protected function getHashMethod(): string
    {
        return 'sha256';
    }
}
