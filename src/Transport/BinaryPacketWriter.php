<?php

namespace Amp\SSH\Transport;

use Amp\Promise;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;

interface BinaryPacketWriter {
    public function updateEncryption(Encryption $encryption, Mac $encryptMac): void;

    public function write($message): Promise;
}
