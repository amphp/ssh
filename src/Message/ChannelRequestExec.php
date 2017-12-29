<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelRequestExec extends ChannelRequest
{
    public $command;

    public function encode(): string
    {
        return parent::encode() . pack(
            'Na*',
            \strlen($this->command),
            $this->command
        );
    }

    public function getType()
    {
        return self::TYPE_EXEC;
    }

    protected function decodeExtraData($extraPayload)
    {
        $commandLength = unpack('N', $extraPayload)[1];
        $this->command = substr($extraPayload, 4, $commandLength);
    }
}
