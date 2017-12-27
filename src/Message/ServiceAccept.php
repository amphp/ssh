<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ServiceAccept implements Message
{
    public $serviceName;

    public function encode(): string
    {
    }

    public static function decode(string $payload)
    {
        $type = unpack('C', $payload)[1];

        if ($type !== self::getNumber()) {
            throw new \RuntimeException('Invalid message');
        }

        $serviceNameLength = unpack('N', $payload, 1)[1];
        $message = new static();
        $message->serviceName = substr($payload, 5, $serviceNameLength);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_SERVICE_ACCEPT;
    }
}
