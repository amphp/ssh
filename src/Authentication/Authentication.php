<?php

declare(strict_types=1);

namespace Amp\SSH\Authentication;

use Amp\Promise;
use Amp\SSH\Transport\BinaryPacketHandler;

interface Authentication
{
    public function authenticate(BinaryPacketHandler $handler, string $sessionId): Promise;
}
