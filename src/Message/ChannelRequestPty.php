<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelRequestPty extends ChannelRequest
{
    public $term = 'xterm';

    public $columns;

    public $rows;

    public $width;

    public $height;

    public $modes = '';

    public function encode(): string
    {
        return parent::encode() . pack(
            'Na*N5a*',
            \strlen($this->term),
            $this->term,
            $this->columns,
            $this->rows,
            $this->width,
            $this->height,
            \strlen($this->modes),
            $this->modes
        );
    }

    public function getType()
    {
        return self::TYPE_PTY;
    }

    protected function decodeExtraData($extraPayload)
    {
        $termLength = unpack('N', $extraPayload)[1];
        $this->term = substr($extraPayload, 4, $termLength);

        [
            $this->columns,
            $this->rows,
            $this->width,
            $this->height,
            $modesLength,
        ] = array_values(unpack('N5', $extraPayload, 4 + $termLength));

        $this->modes = substr($extraPayload, 24 + $termLength, $modesLength);
    }
}
