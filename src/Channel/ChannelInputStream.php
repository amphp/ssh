<?php

namespace Amp\Ssh\Channel;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\IteratorStream;
use Amp\Iterator;
use Amp\Promise;
use Amp\Ssh\Message\ChannelData;
use Amp\Ssh\Message\ChannelExtendedData;

/**
 * @internal
 */
final class ChannelInputStream implements InputStream
{
    private $stream;

    public function __construct(Iterator $iterator)
    {
        $this->stream = new IteratorStream(Iterator\map($iterator, static function ($message) {
            if ($message instanceof ChannelData || $message instanceof ChannelExtendedData) {
                return $message->data;
            }

            return $message;
        }));
    }

    /** {@inheritdoc} */
    public function read(): Promise
    {
        return $this->stream->read();
    }
}
