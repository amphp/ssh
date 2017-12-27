<?php

namespace Amp\SSH\Message;

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
        $type = unpack('C', $payload)[1];

        if ($type !== self::getNumber()) {
            throw new \RuntimeException('Invalid message');
        }

        $message = new static();

        // Read host key
        $fullKeyLength = unpack('N', $payload, 1)[1];
        $fullKey = substr($payload, 5, $fullKeyLength);
        $formatLength = unpack('N', $fullKey)[1];

        $message->hostKeyFormat = substr($fullKey, 4, $formatLength);
        $message->hostKey = $fullKey;

        // Read fBytes
        $fBytesLength = unpack('N', $payload, 5 + $fullKeyLength)[1];

        $message->fBytes = substr($payload, 9 + $fullKeyLength, $fBytesLength);

        // Read signature
        $signatureLength = unpack('N', $payload, 9 + $fullKeyLength + $fBytesLength)[1];
        $signature = substr($payload, 13 + $fullKeyLength + $fBytesLength, $signatureLength);
        $signatureFormatLength = unpack('N', $signature)[1];

        $message->signatureFormat = substr($signature, 4, $signatureFormatLength);
        $message->signature = substr($signature, 4 + $signatureFormatLength);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_KEX_ECDH_REPLY;
    }

}
