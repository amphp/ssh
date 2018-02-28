<?php

namespace Amp\Ssh\Transport;

/**
 * @internal
 */
interface BinaryPacketHandler extends BinaryPacketReader, BinaryPacketWriter {
    public function close();
}
