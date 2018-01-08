<?php

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_string;

class UserAuthBanner implements Message {
    public $message;

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
        $message->message = read_string($payload);
        $message->languageTag = read_string($payload);

        return $message;
    }

    public static function getNumber(): int {
        return self::SSH_MSG_USERAUTH_BANNER;
    }
}
