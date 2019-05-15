<?php

namespace Amp\Ssh\Channel;

use Amp\Iterator;
use Amp\Ssh\Message\ChannelOpen;
use Amp\Ssh\Message\DirectTcpIpChannelOpen;
use Amp\Ssh\Message\Message;
use Amp\Ssh\Transport\BinaryPacketWriter;

class DirectTcpIp extends Channel {
    private $host;

    private $port;

    private $originIp;

    private $originPort;

    public function __construct(BinaryPacketWriter $writer, Iterator $channelMessage, int $channelId, string $host, int $port, string $originIp, int $originPort) {
        parent::__construct($writer, $channelMessage, $channelId);

        $this->host = $host;
        $this->port = $port;
        $this->originIp = $originIp;
        $this->originPort = $originPort;
    }

    protected function createChannelOpenMessage(): Message {
        $channelOpen = new DirectTcpIpChannelOpen();
        $channelOpen->senderChannel = $this->channelId;
        $channelOpen->host = $this->host;
        $channelOpen->port = $this->port;
        $channelOpen->originPort = $this->originPort;
        $channelOpen->originIp = $this->originIp;

        return $channelOpen;
    }

    protected function getType(): string {
        return ChannelOpen::TYPE_DIRECT_TCPIP;
    }
}
