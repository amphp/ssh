<?php

namespace Amp\Ssh\Channel;

use Amp\ByteStream\OutputStream;
use function Amp\call;
use Amp\Promise;
use Amp\Success;

/**
 * @internal
 */
final class ChannelOutputStream implements OutputStream {
    private $writable = true;

    private $channel;

    public function __construct(Channel $channel) {
        $this->channel = $channel;
    }

    /** {@inheritdoc} */
    public function write(string $data): Promise {
        if (!$this->writable) {
            return new Success();
        }

        return $this->channel->data($data);
    }

    /** {@inheritdoc} */
    public function end(string $finalData = ""): Promise {
        return call(function () use ($finalData) {
            yield $this->write($finalData);

            $this->writable = false;

            yield $this->close();
        });
    }

    public function close(): Promise {
        return $this->channel->eof();
    }
}
