<?php

namespace Amp\Ssh\Message;

/**
 * @internal
 */
final class ChannelOpen implements Message {
    const TYPE_SESSION = 'session';
    const TYPE_X11 = 'x11';
    const TYPE_FORWARDED_TCPIP = 'forwarded-tcpip';
    const TYPE_DIRECT_TCPIP = 'direct-tcpip';

    public $channelType;

    public $senderChannel;

    public $initialWindowSize = 0x7FFFFFFF;

    public $maximumPacketSize = 0x4000;

    public function encode(): string {
        return \pack(
            'CNa*N3',
            self::getNumber(),
            \strlen($this->channelType),
            $this->channelType,
            $this->senderChannel,
            $this->initialWindowSize,
            $this->maximumPacketSize
        );
    }

    public static function decode(string $payload) {
        return new static();
    }

    public static function getNumber(): int {
        return self::SSH_MSG_CHANNEL_OPEN;
    }
}
