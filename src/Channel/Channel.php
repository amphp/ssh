<?php

declare(strict_types=1);

namespace Amp\SSH\Channel;

use function Amp\call;
use Amp\Deferred;
use Amp\Promise;
use Amp\SSH\EventEmitter;
use Amp\SSH\Message\ChannelFailure;
use Amp\SSH\Message\ChannelOpen;
use Amp\SSH\Message\ChannelOpenFailure;
use Amp\SSH\Message\ChannelRequest;
use Amp\SSH\Message\Message;
use Amp\SSH\Transport\BinaryPacketWriter;

abstract class Channel extends EventEmitter
{
    protected $channelId;

    /** @var BinaryPacketWriter */
    protected $writer;

    public function __construct(BinaryPacketWriter $writer, int $channelId)
    {
        $this->channelId = $channelId;
        $this->writer = $writer;
    }

    public function getChannelId(): int
    {
        return $this->channelId;
    }

    public function initialize(): Promise
    {
        $openDeferred = new Deferred();

        $this->once(Message::SSH_MSG_CHANNEL_OPEN_CONFIRMATION, function () use($openDeferred) {
            $openDeferred->resolve(true);
        });

        $this->once(Message::SSH_MSG_CHANNEL_OPEN_FAILURE, function (ChannelOpenFailure $channelOpenFailure) use($openDeferred) {
            $openDeferred->fail(new \RuntimeException('Failed to open channel : ' . $channelOpenFailure->description));
        });

        $channelOpen = new ChannelOpen();
        $channelOpen->senderChannel = $this->channelId;
        $channelOpen->channelType = $this->getType();

        Promise\rethrow($this->writer->write($channelOpen));

        return $openDeferred->promise();
    }

    protected function doRequest(ChannelRequest $request): Promise
    {
        if (!$request->wantReply) {
            return $this->writer->write($request);
        }

        $deferred = new Deferred();

        $this->once(Message::SSH_MSG_CHANNEL_SUCCESS, function () use($deferred) {
            $deferred->resolve(true);

            return true;
        });

        $this->once(Message::SSH_MSG_CHANNEL_FAILURE, function () use($deferred) {
            $deferred->fail(new \RuntimeException('Failed to execute request'));

            return true;
        });

        Promise\rethrow($this->writer->write($request));

        return $deferred->promise();
    }

    abstract protected function getType(): string;
}
