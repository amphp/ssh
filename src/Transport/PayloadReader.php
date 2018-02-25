<?php

namespace Amp\Ssh\Transport;

use Amp\Promise;
use Amp\Socket\Socket;
use Amp\Ssh\Encryption;
use Amp\Ssh\Mac;
use function Amp\call;

class PayloadReader implements BinaryPacketReader {
    /** @var Encryption\Decryption */
    private $decryption;

    /** @var Mac\Mac */
    private $decryptMac;

    /** @var int */
    private $readSequenceNumber = 0;

    private $decryptedBuffer = '';

    private $socket;

    private $cryptedBuffer;

    public function __construct(Socket $socket, $buffer) {
        $this->cryptedBuffer = $buffer;
        $this->socket = $socket;
        $this->decryption = new Encryption\None();
        $this->decryptMac = new Mac\None();
    }

    public function updateDecryption(Encryption\Decryption $decryption, Mac\Mac $decryptMac) {
        $this->decryption = $decryption;
        $this->decryptMac = $decryptMac;
    }

    public function read(): Promise {
        /*
        Each packet is in the following format:

          uint32    packet_length
          byte      padding_length
          byte[n1]  payload; n1 = packet_length - padding_length - 1
          byte[n2]  random padding; n2 = padding_length
          byte[m]   mac (Message Authentication Code - MAC); m = mac_length

          packet_length
             The length of the packet in bytes, not including 'mac' or the
             'packet_length' field itself.

          padding_length
             Length of 'random padding' (bytes).

          payload
             The useful contents of the packet.  If compression has been
             negotiated, this field is compressed.  Initially, compression
             MUST be "none".

          random padding
             Arbitrary-length padding, such that the total length of
             (packet_length || padding_length || payload || random padding)
             is a multiple of the cipher block size or 8, whichever is
             larger.  There MUST be at least four bytes of padding.  The
             padding SHOULD consist of random bytes.  The maximum amount of
             padding is 255 bytes.

          mac
             Message Authentication Code.  If message authentication has
             been negotiated, this field contains the MAC bytes.  Initially,
             the MAC algorithm MUST be "none".
         */
        return call(function () {
            $packetLengthRead = yield $this->doReadDecrypted(4);

            if ($packetLengthRead === null) {
                return null;
            }

            $packetLength = \unpack('N', $packetLengthRead)[1];
            $packet = yield $this->doReadDecrypted($packetLength);

            if ($packet === null) {
                return null;
            }

            $paddingLength = \unpack('C', $packet)[1];
            $payload = \substr($packet, 1, $packetLength - $paddingLength - 1);
            $padding = \substr($packet, $packetLength - $paddingLength);
            $mac = yield $this->doReadRaw($this->decryptMac->getLength());

            $computedMac = $this->decryptMac->hash(\pack(
                'NNCa*',
                $this->readSequenceNumber,
                $packetLength,
                $paddingLength,
                $payload . $padding
            ));

            if (!\hash_equals($computedMac, $mac)) {
                throw new \RuntimeException('Invalid mac');
            }

            $this->readSequenceNumber++;

            return $payload;
        });
    }

    private function doReadDecrypted(int $length): Promise {
        return call(function () use ($length) {
            while (\strlen($this->decryptedBuffer) < $length) {
                $rawRead = yield $this->doReadRaw($this->decryption->getBlockSize());

                if ($rawRead === null) {
                    return null;
                }

                $this->decryptedBuffer .= $this->decryption->decrypt($rawRead);
            }

            $read = \substr($this->decryptedBuffer, 0, $length);
            $this->decryptedBuffer = \substr($this->decryptedBuffer, $length);

            return $read;
        });
    }

    private function doReadRaw($length): Promise {
        return call(function () use ($length) {
            while (\strlen($this->cryptedBuffer) < $length) {
                $readed = yield $this->socket->read();

                if ($readed === null) {
                    return null;
                }

                $this->cryptedBuffer .= $readed;
            }

            $read = \substr($this->cryptedBuffer, 0, $length);
            $this->cryptedBuffer = \substr($this->cryptedBuffer, $length);

            return $read;
        });
    }
}
