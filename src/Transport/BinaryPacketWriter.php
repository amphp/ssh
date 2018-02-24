<?php

namespace Amp\Ssh\Transport;

use Amp\Promise;
use Amp\Ssh\Encryption\Encryption;
use Amp\Ssh\Mac\Mac;

interface BinaryPacketWriter {
    public function updateEncryption(Encryption $encryption, Mac $encryptMac);

    public function write($message): Promise;
}
