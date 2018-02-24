<?php

namespace Amp\Ssh\Encryption;

interface Decryption {
    public function getBlockSize(): int;

    public function getKeySize(): int;

    public function decrypt(string $payload): string;

    public function getName(): string;

    public function resetDecrypt(string $key, string $initIv);
}
