<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelFailure implements Message
{
    public $recipientChannel;

    public function encode(): string
    {
        return pack(
            'CN',
            self::getNumber(),
            $this->recipientChannel
        );
    }

    public static function decode(string $payload)
    {
        $message = new static;

        [
            $message->recipientChannel,
        ] = array_values(unpack('N', $payload, 1));

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_FAILURE;
    }
}
