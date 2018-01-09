<?php

namespace Amp\SSH\Transport;

interface BinaryPacketHandler extends BinaryPacketReader, BinaryPacketWriter {
    public function close(): void;
}
