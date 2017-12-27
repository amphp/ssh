<?php

declare(strict_types=1);

namespace Amp\SSH\Encryption\CipherMode;

class Cbc implements CipherMode
{
    public const NAME = 'cbc';

    private const BLOCK_SIZE = 16;

    private $iv;

    public function __construct(string $iv, string $openSSLMethodName)
    {
        $this->iv = $iv;

        if (\strlen($iv) !== \openssl_cipher_iv_length($openSSLMethodName)) {
            throw new \RuntimeException('Invalid initialization vector');
        }
    }

    public function getCurrentIV(): string
    {
        return $this->iv;
    }

    public function updateIV(string $payload): void
    {
        $this->iv = \substr($payload, self::BLOCK_SIZE * -1);
    }
}
