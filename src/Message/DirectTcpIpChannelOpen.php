<?php

namespace Amp\Ssh\Message;

/**
 * @internal
 */
final class DirectTcpIpChannelOpen implements Message {
    public $senderChannel;

    public $initialWindowSize = 0x7FFFFFFF;

    public $maximumPacketSize = 0x4000;

    public $host;

    public $port;

    public $originIp;

    public $originPort;

    public function encode(): string {
        return \pack(
            'CNa*N4a*N2a*N',
            self::getNumber(),
            \strlen(ChannelOpen::TYPE_DIRECT_TCPIP),
            ChannelOpen::TYPE_DIRECT_TCPIP,
            $this->senderChannel,
            $this->initialWindowSize,
            $this->maximumPacketSize,
            \strlen($this->host),
            $this->host,
            $this->port,
            \strlen($this->originIp),
            $this->originIp,
            $this->originPort
        );
    }

    public static function decode(string $payload) {
        return new static();
    }

    public static function getNumber(): int {
        return self::SSH_MSG_CHANNEL_OPEN;
    }
}
