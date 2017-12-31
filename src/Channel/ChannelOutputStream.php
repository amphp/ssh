<?php

namespace Amp\SSH\Channel;

use Amp\ByteStream\OutputStream;
use function Amp\call;
use Amp\Promise;
use Amp\SSH\Message\Message;
use Amp\Success;

class ChannelOutputStream implements OutputStream
{
    private $writable = true;

    private $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $channel->once(Message::SSH_MSG_CHANNEL_EOF, function () {
            $this->writable = false;

            return true;
        });
    }

    /** {@inheritdoc} */
    public function write(string $data): Promise
    {
        if (!$this->writable) {
            return new Success();
        }

        return $this->channel->data($data);
    }

    /** {@inheritdoc} */
    public function end(string $finalData = ""): Promise
    {
        return call(function () use($finalData) {
            yield $this->write($finalData);

            $this->writable = false;

            yield $this->close();
        });
    }

    public function close(): Promise
    {
        return $this->channel->eof();
    }
}