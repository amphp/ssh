<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_byte;
use function Amp\Ssh\Transport\read_string;

/**
 * @internal
 */
final class UserAuthPkOk implements Message {
    private $keyAlgorithm;

    private $keyBlob;

    public function encode(): string {
    }

    public static function decode(string $payload) {
        read_byte($payload);

        $message = new static;
        $message->keyAlgorithm = read_string($payload);
        $message->keyBlob = read_string($payload);

        return $message;
    }

    public static function getNumber(): int {
        return self::SSH_MSG_USERAUTH_PK_OK;
    }
}
