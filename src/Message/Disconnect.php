<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_byte;
use function Amp\Ssh\Transport\read_string;
use function Amp\Ssh\Transport\read_uint32;

class Disconnect implements Message {
    public const SSH_DISCONNECT_HOST_NOT_ALLOWED_TO_CONNECT = 1;
    public const SSH_DISCONNECT_PROTOCOL_ERROR = 2;
    public const SSH_DISCONNECT_KEY_EXCHANGE_FAILED = 3;
    public const SSH_DISCONNECT_RESERVED = 4;
    public const SSH_DISCONNECT_MAC_ERROR = 5;
    public const SSH_DISCONNECT_COMPRESSION_ERROR = 6;
    public const SSH_DISCONNECT_SERVICE_NOT_AVAILABLE = 7;
    public const SSH_DISCONNECT_PROTOCOL_VERSION_NOT_SUPPORTED = 8;
    public const SSH_DISCONNECT_HOST_KEY_NOT_VERIFIABLE = 9;
    public const SSH_DISCONNECT_CONNECTION_LOST = 10;
    public const SSH_DISCONNECT_BY_APPLICATION = 11;
    public const SSH_DISCONNECT_TOO_MANY_CONNECTIONS = 12;
    public const SSH_DISCONNECT_AUTH_CANCELLED_BY_USER = 13;
    public const SSH_DISCONNECT_NO_MORE_AUTH_METHODS_AVAILABLE = 14;
    public const SSH_DISCONNECT_ILLEGAL_USER_NAME = 15;

    public $reasonCode = self::SSH_DISCONNECT_BY_APPLICATION;

    public $description = '';

    public $languageTag = '';

    public function encode(): string {
        return \pack(
            'CN2a*Na*',
            self::getNumber(),
            $this->reasonCode,
            \strlen($this->description),
            $this->description,
            \strlen($this->languageTag),
            $this->languageTag
        );
    }

    public static function decode(string $payload) {
        read_byte($payload);

        $message = new static;
        $message->reasonCode = read_uint32($payload);
        $message->description = read_string($payload);
        $message->languageTag = read_string($payload);
    }

    public static function getNumber(): int {
        return self::SSH_MSG_DISCONNECT;
    }
}
