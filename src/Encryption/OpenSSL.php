<?php

namespace Amp\Ssh\Encryption;

use Amp\Ssh\Encryption\CipherMode\CipherMode;

/**
 * @internal
 */
abstract class OpenSSL implements Decryption, Encryption {
    protected $key;

    /** @var CipherMode */
    private $encryptCipherMode;

    /** @var CipherMode */
    private $decryptCipherMode;

    abstract protected function getOpenSSLMethod(): string;

    abstract protected function createCipherMode(string $iv): CipherMode;

    public function resetEncrypt(string $key, string $initIv) {
        $this->key = $key;
        $this->encryptCipherMode = $this->createCipherMode($initIv);
    }

    public function resetDecrypt(string $key, string $initIv) {
        $this->key = $key;
        $this->decryptCipherMode = $this->createCipherMode($initIv);
    }

    public function crypt(string $payload): string {
        $cryptedText = \openssl_encrypt(
            $payload,
            $this->getOpenSSLMethod(),
            $this->key,
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
            $this->encryptCipherMode->getCurrentIv()
        );

        $this->encryptCipherMode->updateIV($cryptedText);

        return $cryptedText;
    }

    public function decrypt(string $payload): string {
        if ((\strlen($payload) % $this->getBlockSize()) !== 0) {
            throw new \RuntimeException('Payload is not a multiple of crypt block size');
        }

        $decrypted = \openssl_decrypt(
            $payload,
            $this->getOpenSSLMethod(),
            $this->key,
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
            $this->decryptCipherMode->getCurrentIV()
        );

        $this->decryptCipherMode->updateIV($payload);

        return $decrypted;
    }
}
