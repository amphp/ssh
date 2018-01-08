<?php

declare(strict_types=1);

namespace Amp\SSH\Mac;

class None implements Mac {
    public function getLength(): int {
        return 0;
    }

    public function hash(string $payload): string {
        return '';
    }

    public function getName(): string {
        return 'none';
    }

    public function setKey(string $key): void {
    }
}
