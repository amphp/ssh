<?php

namespace Amp\Ssh\Message;

class ChannelRequestShell extends ChannelRequest {
    public function getType() {
        return self::TYPE_SHELL;
    }

    protected function decodeExtraData($extraPayload) {
    }
}
