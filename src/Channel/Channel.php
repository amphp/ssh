<?php

declare(strict_types=1);

namespace Amp\SSH\Channel;

use function Amp\call;
use Amp\Promise;
use Amp\SSH\Loop;
use Amp\SSH\Message\ChannelOpen;

class Channel
{
    /** @var Loop */
    private $loop;

    private $channelId;

    public function __construct(int $channelId)
    {
        $this->channelId = $channelId;
    }

    /**
     * @return int
     */
    public function getChannelId(): int
    {
        return $this->channelId;
    }

    public function initialize(): Promise
    {
        return call(function () {
            $channelOpen = new ChannelOpen();
            $channelOpen->senderChannel = $this->channelId;
            $channelOpen->channelType = ChannelOpen::TYPE_SESSION;

            yield $this->handler->write($channelOpen->encode());
            $packet = yield $this->handler->read();

            var_dump(unpack('C', $packet)[1]);
            $length = unpack('N', $packet, 1)[1];
            $requestName = substr($packet, 5, $length);

            var_dump($requestName);
            var_dump(unpack('C', $packet, 1 +4 + $length)[1]);

            $packet = yield $this->handler->read();
            var_dump($packet);
            var_dump(unpack('C', $packet)[1]);
            var_dump(unpack('N4', $packet, 1));

        });
    }


    public function exec($cmd): Promise
    {

    }

    /**
     * Promise will resolve a Amp\Socket\SocketPool so it can be used for any purpose (HTTP Client, MySQL)
     *
     * @param int $hostPort
     * @param int $remotePort
     *
     * @return Promise
     */
    public function forward(int $hostPort, int $remotePort): Promise
    {

    }
}
