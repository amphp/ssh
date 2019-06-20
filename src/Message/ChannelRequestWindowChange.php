<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_uint32;

/**
 * @internal
 */
final class ChannelRequestWindowChange extends ChannelRequest {
    public $wantReply = false;

    public $columns;

    public $rows;

    public $width;

    public $height;

    public function encode(): string {
        return parent::encode() . \pack(
            'N4',
            $this->columns,
            $this->rows,
            $this->width,
            $this->height
        );
    }

    public function getType() {
        return self::TYPE_WINDOW_CHANGE;
    }

    protected function decodeExtraData($extraPayload) {
        $this->columns = read_uint32($extraPayload);
        $this->rows = read_uint32($extraPayload);
        $this->width = read_uint32($extraPayload);
        $this->height = read_uint32($extraPayload);
    }
}
