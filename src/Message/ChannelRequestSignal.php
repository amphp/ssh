<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelRequestSignal extends ChannelRequest
{
    public $signal;

    private $signalMapping = [
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

    public function encode(): string
    {
        $signal = is_int($this->signal) ? $this->signalMapping[$this->signal] : $this->signal;

        return parent::encode() . pack(
            'Na*',
            \strlen($signal),
            $signal
        );
    }

    public function getType()
    {
        return self::TYPE_SIGNAL;
    }

    protected function decodeExtraData($extraPayload)
    {
        $signalLength = unpack('N', $extraPayload)[1];
        $signal = substr($extraPayload, 4, $signalLength);

        $this->signal = current(array_keys($this->signalMapping, $signal));
    }
}
