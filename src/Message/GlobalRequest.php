<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class GlobalRequest implements Message
{
    public function encode(): string
    {
        return pack(
            'C',
            self::getNumber()
        );
    }

    public static function decode(string $payload)
    {
        return new static();
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_GLOBAL_REQUEST;
    }
}
