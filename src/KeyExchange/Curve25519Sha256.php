<?php

namespace Amp\SSH\KeyExchange;

use function Amp\call;
use Amp\Promise;
use Amp\SSH\BinaryPacketHandler;
use Amp\SSH\Message\KeyExchangeCurveInit;
use Amp\SSH\Message\KeyExchangeCurveReply;
use Amp\SSH\Message\Message;

class Curve25519Sha256 implements KeyExchange
{
    public function getName(): string
    {
        return 'curve25519-sha256@libssh.org';
    }

    public function exchange(BinaryPacketHandler $handler): Promise
    {
        return call(function () use($handler) {
            $secret = \random_bytes(32);
            $message = new KeyExchangeCurveInit();
            $message->exchange = \sodium_crypto_box_publickey_from_secretkey($secret);

            yield $handler->write($message->encode());
            $packet = yield $handler->read();
            $reply = KeyExchangeCurveReply::decode($packet);

            if (\strlen($reply->fBytes) !== 32) {
                throw new \RuntimeException('Invalid reply');
            }

            $key = \sodium_crypto_scalarmult($secret, $reply->fBytes);
            \sodium_memzero($secret);

            return [$key, $message, $reply];
        });
    }

    public function getEBytes(Message $message)
    {
        if (!$message instanceof KeyExchangeCurveInit) {
            throw new \RuntimeException();
        }

        return $message->exchange;
    }

    public function getFBytes(Message $message)
    {
        if (!$message instanceof KeyExchangeCurveReply) {
            throw new \RuntimeException();
        }

        return $message->fBytes;
    }

    public function getHostKey(Message $message)
    {
        if (!$message instanceof KeyExchangeCurveReply) {
            throw new \RuntimeException();
        }

        return $message->hostKey;
    }

    public function hash(string $payload): string
    {
        return \hash('sha256', $payload, true);
    }
}
