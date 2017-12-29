<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelWindowAdjust implements Message
{
    public $recipientChannel;

    public $bytesToAdd;

    public function encode(): string
    {
        return pack(
            'CN2',
            self::getNumber(),
            $this->recipientChannel,
            $this->bytesToAdd
        );
    }

    public static function decode(string $payload)
    {
        $message = new static;

        [
            $message->recipientChannel,
            $message->bytesToAdd
        ] = array_values(unpack('N2', $payload, 1));

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_WINDOW_ADJUST;
    }
}
