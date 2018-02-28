<?php

namespace Amp\Ssh\Transport;

use Amp\Promise;
use Amp\Socket\Socket;
use Amp\Ssh\Encryption;
use Amp\Ssh\Mac;

/**
 * @internal
 */
final class PayloadWriter implements BinaryPacketWriter {
    /** @var Encryption\Encryption */
    private $encryption;

    /** @var Mac\Mac */
    private $encryptMac;

    private $writeSequenceNumber = 0;

    private $socket;

    public function __construct(Socket $socket) {
        $this->socket = $socket;
        $this->encryption = new Encryption\None();
        $this->encryptMac = new Mac\None();
    }

    public function updateEncryption(Encryption\Encryption $encryption, Mac\Mac $encryptMac) {
        $this->encryption = $encryption;
        $this->encryptMac = $encryptMac;
    }

    public function write($payload): Promise {
        $length = 4 + 1 + \strlen($payload);
        $paddingLength = $this->encryption->getBlockSize() - ($length % $this->encryption->getBlockSize());
        $paddingLength += $paddingLength < 4 ? $this->encryption->getBlockSize() : 0;

        $padding = \random_bytes($paddingLength);
        $packetLength = \strlen($payload) + $paddingLength + 1;
        $packet = \pack('NCa*a*', $packetLength, $paddingLength, $payload, $padding);
        $mac = $this->encryptMac->hash(\pack('Na*', $this->writeSequenceNumber, $packet));
        $cipher = $this->encryption->crypt($packet);
        $cipher .= $mac;

        $this->writeSequenceNumber++;

        return $this->socket->write($cipher);
    }
}
