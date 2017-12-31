<?php

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_boolean;
use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_string;

class Debug implements Message
{
    public $alwaysDisplay;

    public $message;

    public $languageTag;

    public function encode(): string
    {
        return pack(
            'CCNa*Na*',
            self::getNumber(),
            $this->alwaysDisplay,
            \strlen($this->message),
            $this->message,
            \strlen($this->languageTag),
            $this->languageTag
        );
    }

    public static function decode(string $payload)
    {
        read_byte($payload);

        $message = new static;
        $message->alwaysDisplay = read_boolean($payload);
        $message->message = read_string($payload);
        $message->languageTag = read_string($payload);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_DEBUG;
    }
}