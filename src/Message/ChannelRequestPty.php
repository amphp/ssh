<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_string;
use function Amp\SSH\Transport\read_uint32;

class ChannelRequestPty extends ChannelRequest
{
    public const MODE_TTY_OP_END = 0;
    public const MODE_VINTR = 1;
    public const MODE_VQUIT = 2;
    public const MODE_VERASE = 3;
    public const MODE_VKILL = 4;
    public const MODE_VEOF = 5;
    public const MODE_VEOL = 6;
    public const MODE_VEOL2 = 7;
    public const MODE_VSTART = 8;
    public const MODE_VSTOP = 9;
    public const MODE_VSUSP = 10;
    public const MODE_VDSUSP = 11;
    public const MODE_VREPRINT = 12;
    public const MODE_VWERASE = 13;
    public const MODE_VLNEXT = 14;
    public const MODE_VFLUSH = 15;
    public const MODE_VSWTCH = 16;
    public const MODE_VSTATUS = 17;
    public const MODE_VDISCARD = 18;
    public const MODE_IGNPAR = 30;
    public const MODE_PARMRK = 31;
    public const MODE_INPCK = 32;
    public const MODE_ISTRIP = 33;
    public const MODE_INLCR = 34;
    public const MODE_IGNCR = 35;
    public const MODE_ICRNL = 36;
    public const MODE_IUCLC = 37;
    public const MODE_IXON = 38;
    public const MODE_IXANY = 39;
    public const MODE_IXOFF = 40;
    public const MODE_IMAXBEL = 41;
    public const MODE_ISIG = 50;
    public const MODE_ICANON = 51;
    public const MODE_XCASE = 52;
    public const MODE_ECHO = 53;
    public const MODE_ECHOE = 54;
    public const MODE_ECHOK = 55;
    public const MODE_ECHONL = 56;
    public const MODE_NOFLSH = 57;
    public const MODE_TOSTOP = 58;
    public const MODE_IEXTEN = 59;
    public const MODE_ECHOCTL = 60;
    public const MODE_ECHOKE = 61;
    public const MODE_PENDIN = 62;
    public const MODE_OPOST = 70;
    public const MODE_OLCUC = 71;
    public const MODE_ONLCR = 72;
    public const MODE_OCRNL = 73;
    public const MODE_ONOCR = 74;
    public const MODE_ONLRET = 75;
    public const MODE_CS7 = 90;
    public const MODE_CS8 = 91;
    public const MODE_PARENB = 92;
    public const MODE_PARODD = 93;
    public const MODE_TTY_OP_ISPEED = 128;
    public const MODE_TTY_OP_OSPEED = 129;
    public const MODE_TTY_OP_NOT_DEFINED = 160;

    public $term = 'xterm';

    public $columns;

    public $rows;

    public $width;

    public $height;

    public $modes = [];

    public function encode(): string
    {
        $modesStr = '';

        foreach ($this->modes as $modeId => $modeValue) {
            $modesStr .= pack('CN', $modeId, $modeValue);
        }

        $modesStr .= pack('C', self::MODE_TTY_OP_END);

        return parent::encode() . pack(
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

    public function getType()
    {
        return self::TYPE_PTY;
    }

    protected function decodeExtraData($extraPayload)
    {
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
