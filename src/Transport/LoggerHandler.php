<?php

namespace Amp\Ssh\Transport;

use Amp\Promise;
use Amp\Ssh\Encryption\Decryption;
use Amp\Ssh\Encryption\Encryption;
use Amp\Ssh\Mac\Mac;
use Amp\Ssh\Message\Debug;
use Amp\Ssh\Message\Message;
use Psr\Log\LoggerInterface;
use function Amp\call;

class LoggerHandler implements BinaryPacketHandler {
    private $handler;

    private $logger;

    public function __construct(BinaryPacketHandler $handler, LoggerInterface $logger) {
        $this->handler = $handler;
        $this->logger = $logger;
    }

    public function updateDecryption(Decryption $decryption, Mac $decryptMac) {
        $this->logger->debug(\sprintf('Decryption (server -> client) updated, cipher: %s, mac: %s', $decryption->getName(), $decryptMac->getName()));

        $this->handler->updateDecryption($decryption, $decryptMac);
    }

    public function updateEncryption(Encryption $encryption, Mac $encryptMac) {
        $this->logger->debug(\sprintf('Encryption (client -> server) updated, cipher: %s, mac: %s', $encryption->getName(), $encryptMac->getName()));

        $this->handler->updateEncryption($encryption, $encryptMac);
    }

    public function read(): Promise {
        return call(function () {
            $packet = yield $this->handler->read();

            if ($packet === null) {
                return null;
            }

            if ($packet instanceof Message) {
                $this->logger->debug(\sprintf('Receive %s packet', \get_class($packet)));
            } else {
                $type = \unpack('C', $packet)[1];
                $this->logger->warning(\sprintf('Unknown packet with number %s received', $type));
            }

            if ($packet instanceof Debug) {
                if ($packet->alwaysDisplay) {
                    $this->logger->info(\sprintf('Debug received from server : %s', $packet->message));
                } else {
                    $this->logger->debug(\sprintf('Debug received from server : %s', $packet->message));
                }
            }

            return $packet;
        });
    }
    public function close() {
        $this->logger->debug(\sprintf('Shuting down ssh connection'));

        $this->handler->close();
    }

    public function write($message): Promise {
        if ($message instanceof Message) {
            $this->logger->debug(\sprintf('Sending %s packet', \get_class($message)));
        }

        return $this->handler->write($message);
    }
}
