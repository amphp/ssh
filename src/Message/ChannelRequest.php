<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

abstract class ChannelRequest implements Message
{
    public const TYPE_PTY = 'pty-req';
    public const TYPE_X11_FORWARDING = 'x11-req';
    public const TYPE_ENV = 'env';
    public const TYPE_SHELL = 'shell';
    public const TYPE_EXEC = 'exec';
    public const TYPE_SUBSYSTEM = 'subsystem';
    public const TYPE_WINDOW_CHANGE = 'subsystem';
    public const TYPE_SIGNAL = 'signal';
    public const TYPE_EXIT_STATUS = 'exit-status';
    public const TYPE_EXIT_SIGNAL = 'exit-signal';

    public $recipientChannel;

    public $wantReply = true;

    private static $typeMapping = [
        self::TYPE_PTY => ChannelRequestPty::class,
        self::TYPE_ENV => ChannelRequestEnv::class,
        self::TYPE_SHELL => ChannelRequestShell::class,
        self::TYPE_EXEC => ChannelRequestExec::class,
        self::TYPE_WINDOW_CHANGE => ChannelRequestWindowChange::class,
        self::TYPE_SIGNAL => ChannelRequestSignal::class,
        self::TYPE_EXIT_STATUS => ChannelRequestExitStatus::class,
        self::TYPE_EXIT_SIGNAL => ChannelRequestExitSignal::class,
    ];

    public function encode(): string
    {
        return pack(
            'CN2a*C',
            self::getNumber(),
            $this->recipientChannel,
            \strlen($this->getType()),
            $this->getType(),
            $this->wantReply
        );
    }

    abstract protected function decodeExtraData($extraPayload);

    abstract public function getType();

    public static function decode(string $payload)
    {
        [
            $recipientChannel,
            $requestTypeLength
        ] = array_values(unpack('N2', $payload, 1));

        $requestType = substr($payload, 9, $requestTypeLength);

        if (!array_key_exists($requestType, self::$typeMapping)) {
            throw new \RuntimeException('Unimplemented request type : ' . $requestType);
        }

        $messageClass = self::$typeMapping[$requestType];

        $message = new $messageClass;
        $message->requestType = $requestType;
        $message->recipientChannel = $recipientChannel;
        $message->wantReply = unpack('C', $payload, 9 + $requestTypeLength)[1];

        $extraData = substr($payload, 10 + $requestTypeLength);
        $message->decodeExtraData($extraData);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_REQUEST;
    }
}
