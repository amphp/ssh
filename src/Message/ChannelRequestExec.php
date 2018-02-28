<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_string;

/**
 * @internal
 */
final class ChannelRequestExec extends ChannelRequest {
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
