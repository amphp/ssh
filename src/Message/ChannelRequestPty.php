<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_byte;
use function Amp\Ssh\Transport\read_string;
use function Amp\Ssh\Transport\read_uint32;

class ChannelRequestPty extends ChannelRequest {
    const MODE_TTY_OP_END = 0;
    const MODE_VINTR = 1;
    const MODE_VQUIT = 2;
    const MODE_VERASE = 3;
    const MODE_VKILL = 4;
    const MODE_VEOF = 5;
    const MODE_VEOL = 6;
    const MODE_VEOL2 = 7;
    const MODE_VSTART = 8;
    const MODE_VSTOP = 9;
    const MODE_VSUSP = 10;
    const MODE_VDSUSP = 11;
    const MODE_VREPRINT = 12;
    const MODE_VWERASE = 13;
    const MODE_VLNEXT = 14;
    const MODE_VFLUSH = 15;
    const MODE_VSWTCH = 16;
    const MODE_VSTATUS = 17;
    const MODE_VDISCARD = 18;
    const MODE_IGNPAR = 30;
    const MODE_PARMRK = 31;
    const MODE_INPCK = 32;
    const MODE_ISTRIP = 33;
    const MODE_INLCR = 34;
    const MODE_IGNCR = 35;
    const MODE_ICRNL = 36;
    const MODE_IUCLC = 37;
    const MODE_IXON = 38;
    const MODE_IXANY = 39;
    const MODE_IXOFF = 40;
    const MODE_IMAXBEL = 41;
    const MODE_ISIG = 50;
    const MODE_ICANON = 51;
    const MODE_XCASE = 52;
    const MODE_ECHO = 53;
    const MODE_ECHOE = 54;
    const MODE_ECHOK = 55;
    const MODE_ECHONL = 56;
    const MODE_NOFLSH = 57;
    const MODE_TOSTOP = 58;
    const MODE_IEXTEN = 59;
    const MODE_ECHOCTL = 60;
    const MODE_ECHOKE = 61;
    const MODE_PENDIN = 62;
    const MODE_OPOST = 70;
    const MODE_OLCUC = 71;
    const MODE_ONLCR = 72;
    const MODE_OCRNL = 73;
    const MODE_ONOCR = 74;
    const MODE_ONLRET = 75;
    const MODE_CS7 = 90;
    const MODE_CS8 = 91;
    const MODE_PARENB = 92;
    const MODE_PARODD = 93;
    const MODE_TTY_OP_ISPEED = 128;
    const MODE_TTY_OP_OSPEED = 129;
    const MODE_TTY_OP_NOT_DEFINED = 160;

    public $term = 'xterm';

    public $columns;

    public $rows;

    public $width;

    public $height;

    public $modes = [];

    public function encode(): string {
        $modesStr = '';

        foreach ($this->modes as $modeId => $modeValue) {
            $modesStr .= \pack('CN', $modeId, $modeValue);
        }

        $modesStr .= \pack('C', self::MODE_TTY_OP_END);

        return parent::encode() . \pack(
            'Na*N5a*',
            \strlen($this->term),
            $this->term,
            $this->columns,
            $this->rows,
            $this->width,
            $this->height,
            \strlen($modesStr),
            $modesStr
        );
    }

    public function getType() {
        return self::TYPE_PTY;
    }

    protected function decodeExtraData($extraPayload) {
        $this->term = read_string($extraPayload);
        $this->columns = read_uint32($extraPayload);
        $this->rows = read_uint32($extraPayload);
        $this->width = read_uint32($extraPayload);
        $this->height = read_uint32($extraPayload);
        $modesString = read_string($extraPayload);

        while (\strlen($modesString) > 0) {
            $modeId = read_byte($modesString);

            if ($modeId === self::MODE_TTY_OP_END || $modeId >= self::MODE_TTY_OP_NOT_DEFINED) {
                break;
            }

            $this->modes[$modeId] = read_uint32($modesString);
        }
    }
}
