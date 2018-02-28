<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_boolean;
use function Amp\Ssh\Transport\read_byte;
use function Amp\Ssh\Transport\read_namelist;

/**
 * @internal
 */
final class UserAuthFailure implements Message {
    public $nextAuthentications;

    public $partialSuccess;

    public function encode(): string {
        return \pack(
            'C',
            self::getNumber()
        );
    }

    public static function decode(string $payload) {
        read_byte($payload);

        $message = new static;
        $message->nextAuthentications = read_namelist($payload);
        $message->partialSuccess = read_boolean($payload);

        return $message;
    }

    public static function getNumber(): int {
        return self::SSH_MSG_USERAUTH_FAILURE;
    }
}
