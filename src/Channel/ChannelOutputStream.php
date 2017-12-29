<?php

namespace Amp\SSH\Channel;

use Amp\ByteStream\OutputStream;
use Amp\Promise;

class ChannelOutputStream implements OutputStream
{
    public function write(string $data): Promise
    {
    }

    public function end(string $finalData = ""): Promise
    {
    }
}