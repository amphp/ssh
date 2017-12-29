<?php

declare(strict_types=1);

namespace Amp\SSH\Channel;

use Amp\SSH\SSHResource;
use Amp\SSH\Message\Message;

class Dispatcher
{
    private $channels = [];

    private $resource;

    private $channelSequence = 0;

    public function __construct(SSHResource $resource)
    {
        $this->resource = $resource;

        $this->createDispatch(Message::SSH_MSG_CHANNEL_OPEN_CONFIRMATION, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_OPEN_FAILURE, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_DATA, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_EXTENDED_DATA, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_EOF, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_CLOSE, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_WINDOW_ADJUST, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_SUCCESS, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_FAILURE, 'recipientChannel');
        $this->createDispatch(Message::SSH_MSG_CHANNEL_REQUEST, 'recipientChannel');
    }

    public function createSession(): Session
    {
        $session = new Session($this->resource, $this->channelSequence);
        $this->channels[$this->channelSequence] = $session;
        ++$this->channelSequence;

        return $session;
    }

    private function createDispatch(int $messageNumber, string $propertyName)
    {
        $closure = function (Message $data) use ($propertyName) {
            $channelId = $data->$propertyName;

            if (!array_key_exists($channelId, $this->channels)) {
                return;
            }

            /** @var Channel $channel */
            $channel = $this->channels[$channelId];
            $channel->emit($data::getNumber(), $data);
        };

        $this->resource->each($messageNumber, $closure);
    }
}
