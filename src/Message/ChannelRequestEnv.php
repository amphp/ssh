<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_string;

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
        $this->name = read_string($extraPayload);
        $this->value = read_string($extraPayload);
    }
}
