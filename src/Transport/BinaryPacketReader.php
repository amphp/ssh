<?php

namespace Amp\Ssh\Transport;

use Amp\Promise;
use Amp\Ssh\Encryption\Decryption;
use Amp\Ssh\Mac\Mac;

interface BinaryPacketReader {
    public function updateDecryption(Decryption $decryption, Mac $decryptMac);

    public function read(): Promise;
}
