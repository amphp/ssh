<?php

namespace Amp\Ssh\KeyExchange;

use Amp\Promise;
use Amp\Ssh\Message\Message;
use Amp\Ssh\Transport\BinaryPacketHandler;

interface KeyExchange {
    public function getName(): string;

    public function exchange(BinaryPacketHandler $handler): Promise;

    public function hash(string $payload): string;

    public function getEBytes(Message $message);

    public function getFBytes(Message $message);

    public function getHostKey(Message $message);
}
