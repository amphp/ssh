<?php

namespace Amp\Ssh\Encryption;

/**
 * @internal
 */
interface Encryption {
    public function getBlockSize(): int;

    public function getKeySize(): int;

    public function crypt(string $payload): string;

    public function getName(): string;

    public function resetEncrypt(string $key, string $initIv);
}
