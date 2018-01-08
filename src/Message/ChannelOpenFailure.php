<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_string;
use function Amp\SSH\Transport\read_uint32;

class ChannelOpenFailure implements Message {
    public $recipientChannel;

    public $reasonCode;

    public $description;

    public $languageTag;

    public function encode(): string {
        return \pack(
            'C',
            self::getNumber()
        );
    }

    public static function decode(string $payload) {
        read_byte($payload);

        $message = new static;
        $message->recipientChannel = read_uint32($payload);
        $message->reasonCode = read_uint32($payload);
        $message->description = read_string($payload);
        $message->languageTag = read_string($payload);

        return $message;
    }

    public static function getNumber(): int {
        return self::SSH_MSG_CHANNEL_OPEN_FAILURE;
    }
}
