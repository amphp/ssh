<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelOpen implements Message
{
    public const TYPE_SESSION = 'session';
    public const TYPE_X11 = 'x11';
    public const TYPE_FORWARDED_TCPIP = 'forwarded-tcpip';
    public const TYPE_DIRECT_TCPIP = 'direct-tcpip';

    public $channelType;

    public $senderChannel;

    public $initialWindowSize = 0x7FFFFFFF;

    public $maximumPacketSize = 0x4000;

    public function encode(): string
    {
        return pack(
            'CNa*N3',
            self::getNumber(),
            \strlen($this->channelType),
            $this->channelType,
            $this->senderChannel,
            $this->initialWindowSize,
            $this->maximumPacketSize
        );
    }

    public static function decode(string $payload)
    {
        // TODO: Implement decode() method.
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_OPEN;
    }
}
