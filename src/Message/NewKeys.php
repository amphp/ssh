<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class NewKeys implements Message
{
    public function encode(): string
    {
        return pack('C', self::getNumber());
    }

    public static function decode(string $payload)
    {
        $type = unpack('C', $payload)[1];

        if ($type !== self::getNumber()) {
            throw new \RuntimeException('Invalid message');
        }

        return new static;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_NEWKEYS;
    }
}
