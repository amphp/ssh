<?php

declare(strict_types=1);

namespace Amp\SSH\Transport;

use function Amp\call;
use Amp\Promise;
use Amp\SSH\Encryption\Decryption;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;
use Amp\SSH\Message\Message;
use Psr\Log\LoggerInterface;

class LoggerHandler implements BinaryPacketHandler
{
    private $handler;

    private $logger;

    public function __construct(BinaryPacketHandler $handler, LoggerInterface $logger)
    {
        $this->handler = $handler;
        $this->logger = $logger;
    }

    public function updateDecryption(Decryption $decryption, Mac $decryptMac): void
    {
        $this->logger->info(sprintf('Decryption updated, using %s and mac %s', $decryption->getName(), $decryptMac->getName()));

        $this->handler->updateDecryption($decryption, $decryptMac);
    }

    public function updateEncryption(Encryption $encryption, Mac $encryptMac): void
    {
        $this->logger->info(sprintf('Encryption updated, using %s and mac %s', $encryption->getName(), $encryptMac->getName()));

        $this->handler->updateEncryption($encryption, $encryptMac);
    }

    public function read(): Promise
    {
        return call(function () {
            $packet = yield $this->handler->read();

            if ($packet instanceof Message) {
                $this->logger->info(sprintf('Receive %s packet', \get_class($packet)));
            } else {
                $type = unpack('C', $packet)[1];
                $this->logger->info(sprintf('Receive %s packet', $type));
            }

            return $packet;
        });
    }

    public function write($message): Promise
    {
        if ($message instanceof Message) {
            $this->logger->info(sprintf('Sending %s packet', \get_class($message)));
        }

        return $this->handler->write($message);
    }
}
