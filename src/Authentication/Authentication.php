<?php

namespace Amp\Ssh\Authentication;

use Amp\Promise;
use Amp\Ssh\Transport\BinaryPacketHandler;

interface Authentication {
    public function authenticate(BinaryPacketHandler $handler, string $sessionId): Promise;
}
