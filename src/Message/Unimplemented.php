<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_byte;
use function Amp\Ssh\Transport\read_uint32;

/**
 * @internal
 */
final class Unimplemented implements Message {
    public $packetSequenceNumberRejected;

    public function encode(): string {
        return \pack(
            'CN',
            self::getNumber(),
            $this->packetSequenceNumberRejected
        );
    }

    public static function decode(string $payload) {
        read_byte($payload);

        $message = new static;
        $message->packetSequenceNumberRejected = read_uint32($payload);

        return $message;
    }

    public static function getNumber(): int {
        return self::SSH_MSG_UNIMPLEMENTED;
    }
}
