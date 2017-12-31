<?php

declare(strict_types=1);

namespace Amp\SSH\Transport;

interface BinaryPacketHandler extends BinaryPacketReader, BinaryPacketWriter
{
    public function close(): void;
}
