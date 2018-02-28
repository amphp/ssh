<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_byte;
use function Amp\Ssh\Transport\read_string;

/**
 * @internal
 */
final class Ignore implements Message {
    public $data;

    public function encode(): string {
        return \pack('CNa*', self::getNumber(), \strlen($this->data), $this->data);
    }

    public static function decode(string $payload) {
        read_byte($payload);

        $message = new static;
        $message->data = read_string($payload);

        return $message;
    }

    public static function getNumber(): int {
        return self::SSH_MSG_IGNORE;
    }
}
