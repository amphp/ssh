<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelRequestWindowChange extends ChannelRequest
{
    public $columns;

    public $rows;

    public $width;

    public $height;

    public $modes;

    public function encode(): string
    {
        return parent::encode() . pack(
            'N4*',
            $this->columns,
            $this->rows,
            $this->width,
            $this->height
        );
    }

    public function getType()
    {
        return self::TYPE_WINDOW_CHANGE;
    }

    protected function decodeExtraData($extraPayload)
    {
        [
            $this->columns,
            $this->rows,
            $this->width,
            $this->height,
        ] = array_values(unpack('N4', $extraPayload));
    }
}
