<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelRequestExitStatus extends ChannelRequest
{
    public $wantReply = false;

    public $code;

    public function encode(): string
    {
        return parent::encode() . pack(
            'N',
            $this->code
        );
    }

    public function getType()
    {
        return self::TYPE_EXIT_STATUS;
    }

    protected function decodeExtraData($extraPayload)
    {
        $this->code = unpack('N', $extraPayload)[1];
    }
}
