<?php

namespace Amp\Ssh\Channel;

use Amp\ByteStream\InputStream;
use Amp\Iterator;
use Amp\Promise;
use Amp\Ssh\Message\ChannelData;
use Amp\Ssh\Message\ChannelExtendedData;
use Amp\Success;
use function Amp\call;

/**
 * @internal
 */
class ChannelInputStream implements InputStream {
    private $readable = true;

    private $iterator;

    public function __construct(Iterator $iterator) {
        $this->iterator = $iterator;
    }

    /** {@inheritdoc} */
    public function read(): Promise {
        if (!$this->readable) {
            return new Success; // Resolve with null on closed stream.
        }

        return call(function () {
            $advanced = yield $this->iterator->advance();

            if (!$advanced) {
                $this->readable = false;

                return null;
            }

            $message = $this->iterator->getCurrent();

            if ($message instanceof ChannelData || $message instanceof ChannelExtendedData) {
                return $message->data;
            }

            return $message;
        });
    }
}
