<?php

declare(strict_types=1);

namespace Amp\SSH\Transport;

use Amp\Promise;
use Amp\SSH\Encryption\Decryption;
use Amp\SSH\Mac\Mac;

interface BinaryPacketReader {
    public function updateDecryption(Decryption $decryption, Mac $decryptMac): void;

    public function read(): Promise;
}
