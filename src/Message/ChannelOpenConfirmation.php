<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_uint32;

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
        read_byte($payload);

        $message = new static;
        $message->recipientChannel = read_uint32($payload);
        $message->senderChannel = read_uint32($payload);
        $message->initialWindowSize = read_uint32($payload);
        $message->maximumPacketSize = read_uint32($payload);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_OPEN_CONFIRMATION;
    }
}
