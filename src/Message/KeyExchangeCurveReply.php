<?php

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_string;

class KeyExchangeCurveReply implements Message
{
    public $hostKey;
    public $hostKeyFormat;
    public $fBytes;
    public $signature;
    public $signatureFormat;

    public function encode(): string
    {
        throw new \RuntimeException('Not implemented');
    }

    public static function decode(string $payload)
    {
        read_byte($payload);

        $message = new static();

        // Read host key
        $fullKey = $message->hostKey = read_string($payload);
        $message->hostKeyFormat = read_string($fullKey);

        // Read fBytes
        $message->fBytes = read_string($payload);

        // Read signature
        $signature = $message->signature = read_string($payload);
        $message->signatureFormat = read_string($signature);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_KEX_ECDH_REPLY;
    }

}
