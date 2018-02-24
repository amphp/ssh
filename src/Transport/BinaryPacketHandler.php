<?php

namespace Amp\Ssh\Transport;

interface BinaryPacketHandler extends BinaryPacketReader, BinaryPacketWriter {
    public function close();
}
