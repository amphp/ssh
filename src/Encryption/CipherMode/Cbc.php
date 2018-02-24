<?php

namespace Amp\Ssh\Encryption\CipherMode;

class Cbc implements CipherMode {
    const NAME = 'cbc';

    const BLOCK_SIZE = 16;

    private $iv;

    public function __construct(string $iv, string $openSSLMethodName) {
        $this->iv = $iv;

        if (\strlen($iv) !== \openssl_cipher_iv_length($openSSLMethodName)) {
            throw new \RuntimeException('Invalid initialization vector');
        }
    }

    public function getCurrentIV(): string {
        return $this->iv;
    }

    public function updateIV(string $payload) {
        $this->iv = \substr($payload, self::BLOCK_SIZE * -1);
    }
}
