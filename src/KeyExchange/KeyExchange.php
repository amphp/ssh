<?php

namespace Amp\SSH\KeyExchange;

use Amp\Promise;
use Amp\SSH\Message\Message;
use Amp\SSH\Transport\BinaryPacketHandler;

interface KeyExchange {
    public function getName(): string;

    public function exchange(BinaryPacketHandler $handler): Promise;

    public function hash(string $payload): string;

    public function getEBytes(Message $message);

    public function getFBytes(Message $message);

    public function getHostKey(Message $message);
}
