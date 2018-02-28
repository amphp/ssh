<?php

namespace Amp\Ssh\Mac;

/**
 * @internal
 */
interface Mac {
    public function getLength(): int;

    public function hash(string $payload): string;

    public function getName(): string;

    public function setKey(string $key);
}
