<?php

declare(strict_types=1);

namespace Amp\SSH\Authentication;

use Amp\Promise;
use Amp\SSH\BinaryPacketHandler;

interface Authentication
{
    public function authenticate(BinaryPacketHandler $handler): Promise;
}
