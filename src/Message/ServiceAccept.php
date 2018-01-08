<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_string;

class ServiceAccept implements Message {
    public $serviceName;

    public function encode(): string {
    }

    public static function decode(string $payload) {
        read_byte($payload);

        $message = new static();
        $message->serviceName = read_string($payload);

        return $message;
    }

    public static function getNumber(): int {
        return self::SSH_MSG_SERVICE_ACCEPT;
    }
}
