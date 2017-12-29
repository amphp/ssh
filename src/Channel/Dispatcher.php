<?php

declare(strict_types=1);

namespace Amp\SSH\Channel;

use Amp\Promise;
use Amp\SSH\Loop;
use Amp\SSH\Message\ChannelClose;
use Amp\SSH\Message\ChannelData;
use Amp\SSH\Message\ChannelEof;
use Amp\SSH\Message\ChannelExtendedData;
use Amp\SSH\Message\ChannelFailure;
use Amp\SSH\Message\ChannelOpen;
use Amp\SSH\Message\ChannelOpenConfirmation;
use Amp\SSH\Message\ChannelOpenFailure;
use Amp\SSH\Message\ChannelRequest;
use Amp\SSH\Message\ChannelSuccess;
use Amp\SSH\Message\ChannelWindowAdjust;
use Amp\SSH\Message\Message;

class Dispatcher implements ChannelListener
{
    private $channels = [];

    private $loop;

    private $channelSequence = 0;

    public function __construct(Loop $loop)
    {
        $this->loop = $loop;
        $this->loop->on(Message::SSH_MSG_CHANNEL_OPEN_CONFIRMATION, (new \ReflectionMethod($this, 'onChannelOpenConfirmation'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_OPEN_FAILURE, (new \ReflectionMethod($this, 'onChannelOpenFailure'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_DATA, (new \ReflectionMethod($this, 'onChannelData'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_EXTENDED_DATA, (new \ReflectionMethod($this, 'onChannelExtendedData'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_EOF, (new \ReflectionMethod($this, 'onChannelEof'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_CLOSE, (new \ReflectionMethod($this, 'onChannelClose'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_WINDOW_ADJUST, (new \ReflectionMethod($this, 'onChannelWindowAdjust'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_SUCCESS, (new \ReflectionMethod($this, 'onChannelSuccess'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_FAILURE, (new \ReflectionMethod($this, 'onChannelFailure'))->getClosure($this));
        $this->loop->on(Message::SSH_MSG_CHANNEL_REQUEST, (new \ReflectionMethod($this, 'onChannelRequest'))->getClosure($this));
    }

    public function createSession(): Promise
    {
        $session = new Session($this->loop, $this->channelSequence);
        $this->channels[$this->channelSequence] = $session;
        ++$this->channelSequence;

        return $session->initialize();
    }

    public function addChannel(Channel $channel): void
    {
        $this->channels[$channel->getChannelId()] = $channel;
    }

    private function dispatch($channelId, $method, $message)
    {
        if (!array_key_exists($channelId, $this->channels)) {
            return;
        }

        /** @var Channel $channel */
        $channel = $this->channels[$channelId];
        $channel->$method($message);
    }



    public function onChannelOpen(ChannelOpen $channelOpen)
    {
        // TODO: Implement onChannelOpen() method.
    }

    public function onChannelOpenConfirmation(ChannelOpenConfirmation $channelOpenConfirmation)
    {
        $this->dispatch($channelOpenConfirmation->recipientChannel, 'onChannelOpenConfirmation', $channelOpenConfirmation);
    }

    public function onChannelOpenFailure(ChannelOpenFailure $channelFailure)
    {
        $this->dispatch($channelFailure->recipientChannel, 'onChannelOpenFailure', $channelFailure);
    }

    public function onChannelData(ChannelData $channelData)
    {
        $this->dispatch($channelData->recipientChannel, 'onChannelData', $channelData);
    }

    public function onChannelExtendedData(ChannelExtendedData $channelExtendedData)
    {
        $this->dispatch($channelExtendedData->recipientChannel, 'onChannelExtendedData', $channelExtendedData);
    }

    public function onChannelEof(ChannelEof $channelEof)
    {
        $this->dispatch($channelEof->recipientChannel, 'onChannelEof', $channelEof);
    }

    public function onChannelClose(ChannelClose $channelClose)
    {
        $this->dispatch($channelClose->recipientChannel, 'onChannelClose', $channelClose);
    }

    public function onChannelWindowAdjust(ChannelWindowAdjust $channelWindowAdjust)
    {
        $this->dispatch($channelWindowAdjust->recipientChannel, 'onChannelWindowAdjust', $channelWindowAdjust);
    }

    public function onChannelSuccess(ChannelSuccess $channelSuccess)
    {
        $this->dispatch($channelSuccess->recipientChannel, 'onChannelSuccess', $channelSuccess);
    }

    public function onChannelFailure(ChannelFailure $channelFailure)
    {
        $this->dispatch($channelFailure->recipientChannel, 'onChannelFailure', $channelFailure);
    }

    public function onChannelRequest(ChannelRequest $channelRequest)
    {
        $this->dispatch($channelRequest->recipientChannel, 'onChannelRequest', $channelRequest);
    }
}
