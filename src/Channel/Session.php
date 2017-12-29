<?php

namespace Amp\SSH\Channel;

use Amp\SSH\Message\ChannelOpen;
use Amp\SSH\Message\ChannelRequestExec;

class Session extends Channel
{
    protected function getType(): string
    {
        return ChannelOpen::TYPE_SESSION;
    }

    public function exec($command)
    {
        $request = new ChannelRequestExec();
        $request->recipientChannel = $this->channelId;
        $request->command = $command;

        return $this->writer->write($request);
    }
}