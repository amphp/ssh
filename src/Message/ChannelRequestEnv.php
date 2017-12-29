<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelRequestEnv extends ChannelRequest
{
    public $name;

    public $value;

    public function encode(): string
    {
        return parent::encode() . pack(
            'Na*Na*',
            \strlen($this->name),
            $this->name,
            \strlen($this->value),
            $this->value
        );
    }

    public function getType()
    {
        return self::TYPE_ENV;
    }

    protected function decodeExtraData($extraPayload)
    {
        $nameLength = unpack('N', $extraPayload)[1];
        $this->name = substr($extraPayload, 4, $nameLength);
        $valueLength = unpack('N', $extraPayload, 4 + $nameLength)[1];
        $this->value = substr($extraPayload, 8 + $nameLength, $valueLength);
    }
}
