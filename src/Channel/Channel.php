<?php

declare(strict_types=1);

namespace Amp\SSH\Channel;

use function Amp\call;
use Amp\Deferred;
use Amp\Promise;
use Amp\SSH\Message\ChannelClose;
use Amp\SSH\Message\ChannelData;
use Amp\SSH\Message\ChannelEof;
use Amp\SSH\Message\ChannelExtendedData;
use Amp\SSH\Message\ChannelFailure;
use Amp\SSH\Message\ChannelOpen;
use Amp\SSH\Message\ChannelOpenConfirmation;
use Amp\SSH\Message\ChannelOpenFailure;
use Amp\SSH\Message\ChannelRequest;
use Amp\SSH\Message\ChannelRequestExitStatus;
use Amp\SSH\Message\ChannelSuccess;
use Amp\SSH\Message\ChannelWindowAdjust;
use Amp\SSH\Transport\BinaryPacketWriter;

abstract class Channel implements ChannelListener
{
    protected $channelId;

    /** @var BinaryPacketWriter */
    protected $writer;

    /** @var Deferred */
    private $openDeferred;

    public function __construct(BinaryPacketWriter $writer, int $channelId)
    {
        $this->channelId = $channelId;
        $this->writer = $writer;
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
        $channelOpen = new ChannelOpen();
        $channelOpen->senderChannel = $this->channelId;
        $channelOpen->channelType = $this->getType();

        $this->openDeferred = new Deferred();

        Promise\rethrow($this->writer->write($channelOpen));

        return $this->openDeferred->promise();
    }

    abstract protected function getType(): string;

    public function onChannelOpen(ChannelOpen $channelOpen)
    {
    }

    public function onChannelOpenConfirmation(ChannelOpenConfirmation $channelSuccess)
    {
        if (null === $this->openDeferred) {
            return;
        }

        $this->openDeferred->resolve($this);
    }

    public function onChannelOpenFailure(ChannelOpenFailure $channelFailure)
    {
        if (null === $this->openDeferred) {
            return;
        }

        $this->openDeferred->fail(new \RuntimeException('Failed to open channel : ' . $channelFailure->description));
    }

    public function onChannelData(ChannelData $channelData)
    {
        var_dump($channelData->data);
    }

    public function onChannelExtendedData(ChannelExtendedData $channelExtraData)
    {
        var_dump($channelExtraData->data);
    }

    public function onChannelEof(ChannelEof $channelEof)
    {
    }

    public function onChannelClose(ChannelClose $channelClose)
    {
    }

    public function onChannelWindowAdjust(ChannelWindowAdjust $channelWindowAdjust)
    {
    }

    public function onChannelSuccess(ChannelSuccess $channelSuccess)
    {
    }

    public function onChannelFailure(ChannelFailure $channelFailure)
    {
    }

    public function onChannelRequest(ChannelRequest $channelRequest)
    {
        if ($channelRequest instanceof ChannelRequestExitStatus) {
            var_dump($channelRequest->code);
        }
    }
}
