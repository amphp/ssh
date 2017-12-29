<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelExtendedData implements Message
{
    public const SSH_EXTENDED_DATA_STDERR = 1;

    public $recipientChannel;

    public $data;

    public $dataType;

    public function encode(): string
    {
        return pack(
            'CN3a*',
            self::getNumber(),
            $this->recipientChannel,
            $this->dataType,
            \strlen($this->data),
            $this->data
        );
    }

    public static function decode(string $payload)
    {
        $message = new static;

        [
            $message->recipientChannel,
            $message->dataType,
            $dataLength,
        ] = array_values(unpack('N3', $payload, 1));

        $message->data = substr($payload, 13, $dataLength);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_EXTENDED_DATA;
    }
}
