<?php

namespace Amp\SSH\KeyExchange;

use Amp\Promise;
use Amp\SSH\Message\KeyExchangeCurveInit;
use Amp\SSH\Message\KeyExchangeCurveReply;
use Amp\SSH\Message\Message;
use Amp\SSH\Transport\BinaryPacketHandler;
use function Amp\call;

class Curve25519Sha256 implements KeyExchange {
    public function getName(): string {
        return 'curve25519-sha256@libssh.org';
    }

    public function exchange(BinaryPacketHandler $handler): Promise {
        return call(function () use ($handler) {
            $secret = \random_bytes(32);
            $message = new KeyExchangeCurveInit();
            $message->exchange = \sodium_crypto_box_publickey_from_secretkey($secret);

            yield $handler->write($message);
            $packet = yield $handler->read();

            if (!$packet instanceof KeyExchangeCurveReply) {
                throw new \RuntimeException('Invalid reply');
            }

            if (\strlen($packet->fBytes) !== 32) {
                throw new \RuntimeException('Invalid reply');
            }

            $key = \sodium_crypto_scalarmult($secret, $packet->fBytes);
            \sodium_memzero($secret);

            // Two's complement representation
            if (\ord($key[0]) & 0x80) {
                $key = \chr(0) . $key;
            }

            return [$key, $message, $packet];
        });
    }

    public function getEBytes(Message $message) {
        if (!$message instanceof KeyExchangeCurveInit) {
            throw new \RuntimeException();
        }

        return $message->exchange;
    }

    public function getFBytes(Message $message) {
        if (!$message instanceof KeyExchangeCurveReply) {
            throw new \RuntimeException();
        }

        return $message->fBytes;
    }

    public function getHostKey(Message $message) {
        if (!$message instanceof KeyExchangeCurveReply) {
            throw new \RuntimeException();
        }

        return $message->hostKey;
    }

    public function hash(string $payload): string {
        return \hash('sha256', $payload, true);
    }
}
