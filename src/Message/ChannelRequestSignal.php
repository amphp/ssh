<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_string;

class ChannelRequestSignal extends ChannelRequest {
    public $signal;

    private static $signalMapping = [
        SIGABRT => 'ABRT',
        SIGALRM => 'ALRM',
        SIGFPE => 'FPE',
        SIGHUP => 'HUP',
        SIGILL => 'ILL',
        SIGINT => 'INT',
        SIGKILL => 'KILL',
        SIGPIPE => 'PIPE',
        SIGQUIT => 'QUIT',
        SIGSEGV => 'SEGV',
        SIGTERM => 'TERM',
        SIGUSR1 => 'USR1',
        SIGUSR2 => 'USR2',
    ];

    public $wantReply = false;

    public function encode(): string {
        $signal = \is_int($this->signal) ? self::$signalMapping[$this->signal] : $this->signal;

        return parent::encode() . \pack(
            'Na*',
            \strlen($signal),
            $signal
        );
    }

    public function getType() {
        return self::TYPE_SIGNAL;
    }

    protected function decodeExtraData($extraPayload) {
        $signal = read_string($extraPayload);

        $this->signal = \current(\array_keys(self::$signalMapping, $signal));
    }
}
