<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class ChannelRequestExitSignal extends ChannelRequest
{
    public $signal;

    public $coreDumped;

    public $errorMessage;

    public $languageTag;

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
            'Na*C',
            \strlen($signal),
            $signal,
            $this->coreDumped,
            \strlen($this->errorMessage),
            $this->errorMessage,
            \strlen($this->languageTag),
            $this->languageTag
        );
    }

    public function getType()
    {
        return self::TYPE_EXIT_SIGNAL;
    }

    protected function decodeExtraData($extraPayload)
    {
        $signalLength = unpack('N', $extraPayload)[1];
        $signal = substr($extraPayload, 4, $signalLength);
        $this->signal = current(array_keys($this->signalMapping, $signal));
        $this->coreDumped = (bool) unpack('C', $extraPayload, 4 + $signalLength)[1];

        $errorMessageLength = unpack('N', $extraPayload, 5 + $signalLength);
        $this->errorMessage = substr($extraPayload, 9 + $signalLength, $errorMessageLength);
        $languageTagLength = unpack('N', $extraPayload, 9 + $signalLength + $errorMessageLength);
        $this->errorMessage = substr($extraPayload, 13 + $signalLength + $errorMessageLength, $languageTagLength);
    }
}
