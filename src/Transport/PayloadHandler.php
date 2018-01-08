<?php

namespace Amp\SSH\Transport;

use Amp\Promise;
use Amp\Socket\Socket;
use Amp\SSH\Encryption\Decryption;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;

class PayloadHandler implements BinaryPacketHandler {
    private $reader;

    private $writer;

    private $socket;

    public function __construct(Socket $socket, $buffer) {
        $this->reader = new PayloadReader($socket, $buffer);
        $this->writer = new PayloadWriter($socket);
        $this->socket = $socket;
    }

    public function updateDecryption(Decryption $decryption, Mac $decryptMac): void {
        $this->reader->updateDecryption($decryption, $decryptMac);
    }

    public function updateEncryption(Encryption $encryption, Mac $encryptMac): void {
        $this->writer->updateEncryption($encryption, $encryptMac);
    }

    public function read(): Promise {
        return $this->reader->read();
    }

    public function write($message): Promise {
        return $this->writer->write($message);
    }

    public function close(): void {
        $this->socket->close();
    }
}
