<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelData implements Message
{
    public $recipientChannel;

    public $data;

    public function encode(): string
    {
        return pack(
            'CN2a*',
            self::getNumber(),
            $this->recipientChannel,
            \strlen($this->data),
            $this->data
        );
    }

    public static function decode(string $payload)
    {
        $message = new static;

        [
            $message->recipientChannel,
            $dataLength,
        ] = array_values(unpack('N2', $payload, 1));

        $message->data = substr($payload, 9, $dataLength);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_DATA;
    }
}
