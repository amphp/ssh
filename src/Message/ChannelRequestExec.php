<?php

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_string;

class ChannelRequestExec extends ChannelRequest {
    public $command;

    public function encode(): string {
        return parent::encode() . \pack(
            'Na*',
            \strlen($this->command),
            $this->command
        );
    }

    public function getType() {
        return self::TYPE_EXEC;
    }

    protected function decodeExtraData($extraPayload) {
        $this->command = read_string($extraPayload);
    }
}
