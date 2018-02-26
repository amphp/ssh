<?php

namespace Amp\Ssh\Channel;

use Amp\Ssh\Message\ChannelFailure;
use Throwable;

class ChannelFailureException extends \RuntimeException {
    private $channelFailure;

    public function __construct(string $message, ChannelFailure $channelFailure, Throwable $previous = null) {
        parent::__construct($message, 0, $previous);

        $this->channelFailure = $channelFailure;
    }

    public function getChannelFailure() {
        return $this->channelFailure;
    }
}
