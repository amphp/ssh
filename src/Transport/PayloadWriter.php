<?php

declare(strict_types=1);

namespace Amp\SSH\Transport;

use Amp\Promise;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;
use Amp\SSH\Message\Message;

class PayloadWriter implements BinaryPacketWriter
{
    /** @var Encryption */
    private $encryption;

    /** @var Mac */
    private $encryptMac;

    public function updateEncryption(Encryption $encryption, Mac $encryptMac): void
    {
        // TODO: Implement updateEncryption() method.
    }

    public function write(Message $message): Promise
    {
        // TODO: Implement write() method.
    }
}
