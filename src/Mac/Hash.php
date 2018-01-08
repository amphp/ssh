<?php

declare(strict_types=1);

namespace Amp\SSH\Mac;

class Hash implements Mac {
    private $method;

    private $name;

    private $length;

    private $key;

    public function __construct(string $method, string $name, int $length) {
        $this->method = $method;
        $this->name = $name;
        $this->length = $length;
    }

    public function getLength(): int {
        return $this->length;
    }

    public function setKey(string $key): void {
        $this->key = $key;
    }

    public function hash(string $payload): string {
        if ($this->key === null) {
            throw new \RuntimeException('Key required');
        }

        return \hash_hmac($this->method, $payload, $this->key, true);
    }

    public function getName(): string {
        return $this->name;
    }
}
