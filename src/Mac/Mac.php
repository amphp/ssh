<?php

namespace Amp\SSH\Mac;

interface Mac {
    public function getLength(): int;

    public function hash(string $payload): string;

    public function getName(): string;

    public function setKey(string $key): void;
}
