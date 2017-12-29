<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelOpenFailure implements Message
{
    public $recipientChannel;

    public $reasonCode;

    public $description;

    public $languageTag;

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
            $message->reasonCode,
            $descriptionLength
        ] = array_values(unpack('N3', $payload, 1));

        $message->description = substr($payload, 13, $descriptionLength);
        $languageTagLength = unpack('N', $payload, 13 + $descriptionLength)[1];
        $message->languageTag = substr($payload, 17 + $descriptionLength, $languageTagLength);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_OPEN_FAILURE;
    }
}
