<?php

declare(strict_types=1);

namespace Amp\SSH\Channel;

use Amp\SSH\Loop;
use Amp\SSH\Message\Message;

class Dispatcher
{
    private $channels = [];

    public function __construct(Loop $loop)
    {
        $loop->on(Message::SSH_MSG_CHANNEL_SUCCESS, (new \ReflectionMethod($this, 'onFrameAttached'))->getClosure($this));
    }

    public function addChannel(Channel $channel)
    {
        $this->channels[$channel->getChannelId()] = $channel;
    }
}
