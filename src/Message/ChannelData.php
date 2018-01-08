<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_string;
use function Amp\SSH\Transport\read_uint32;

class ChannelData implements Message {
    public $recipientChannel;

    public $data;

    public function encode(): string {
        return \pack(
            'CN2a*',
            self::getNumber(),
            $this->recipientChannel,
            \strlen($this->data),
            $this->data
        );
    }

    public static function decode(string $payload) {
        read_byte($payload);

        $message = new static;
        $message->recipientChannel = read_uint32($payload);
        $message->data = read_string($payload);

        return $message;
    }

    public static function getNumber(): int {
        return self::SSH_MSG_CHANNEL_DATA;
    }
}
