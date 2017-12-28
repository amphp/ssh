<?php

declare(strict_types=1);

namespace Amp\SSH\Transport;

use Amp\Promise;
use Amp\SSH\Encryption\Decryption;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;

class LoggerHandler implements BinaryPacketHandler
{
    private $handler;

    private $logger;

    public function __construct(BinaryPacketHandler $handler, $logger)
    {
        $this->handler = $handler;
        $this->logger = $logger;
    }

    public function updateDecryption(Decryption $decryption, Mac $decryptMac): void
    {
        $this->handler->updateDecryption($decryption, $decryptMac);
    }

    public function updateEncryption(Encryption $encryption, Mac $encryptMac): void
    {
        $this->handler->updateEncryption($encryption, $encryptMac);
    }

    public function read(): Promise
    {
        return $this->handler->read();
    }

    public function write($message): Promise
    {
        return $this->handler->write($message);
    }
}
