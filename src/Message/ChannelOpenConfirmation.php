<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelOpenConfirmation implements Message
{
    public $recipientChannel;

    public $senderChannel;

    public $initialWindowSize;

    public $maximumPacketSize;

    public function encode(): string
    {
        return pack(
            'C',
            self::getNumber()
        );
    }

    public static function decode(string $payload)
    {
        $message = new static;

        [
            $message->recipientChannel,
            $message->senderChannel,
            $message->initialWindowSize,
            $message->maximumPacketSize,
        ] = array_values(unpack('N4', $payload, 1));

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_OPEN_CONFIRMATION;
    }
}
