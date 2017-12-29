<?php

namespace Amp\SSH\Channel;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\PendingReadError;
use Amp\Deferred;
use Amp\Promise;
use Amp\SSH\Message\ChannelData;
use Amp\SSH\Message\ChannelEof;
use Amp\SSH\Message\ChannelExtendedData;
use Amp\SSH\Message\Message;
use Amp\Success;

class ChannelInputStream implements InputStream
{
    /** @var Deferred */
    private $deferred;

    private $readable = true;

    private $buffer = '';

    public function __construct(Channel $channel, $message = Message::SSH_MSG_CHANNEL_DATA)
    {
        $channel->each($message, function ($message) {
            if (!$message instanceof ChannelData && (!$message instanceof ChannelExtendedData || $message->dataType !== ChannelExtendedData::SSH_EXTENDED_DATA_STDERR)) {
                return;
            }

            if ($this->deferred !== null) {
                $deferred = $this->deferred;
                $this->deferred = null;

                $deferred->resolve($message->data);

                return;
            }

            $this->buffer .= $message->data;
        });

        $channel->once(Message::SSH_MSG_CHANNEL_EOF, function (ChannelEof $channelEof) {
            $this->readable = false;

            if ($this->deferred !== null) {
                $deferred = $this->deferred;
                $this->deferred = null;

                $deferred->resolve();
            }

            return true;
        });
    }

    /** {@inheritdoc} */
    public function read(): Promise
    {
        if ($this->deferred !== null) {
            throw new PendingReadError();
        }

        if (!$this->readable) {
            return new Success; // Resolve with null on closed stream.
        }

        if (!empty($this->buffer)) {
            $buffer = $this->buffer;
            $this->buffer = '';

            return new Success($buffer);
        }

        $this->deferred = new Deferred;

        return $this->deferred->promise();
    }
}