<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_boolean;
use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_string;
use function Amp\SSH\Transport\read_uint32;

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

    public $requestType;

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
        read_byte($payload);
        $recipientChannel = read_uint32($payload);
        $requestType = read_string($payload);

        if (!array_key_exists($requestType, self::$typeMapping)) {
            throw new \RuntimeException('Unimplemented request type : ' . $requestType);
        }

        $messageClass = self::$typeMapping[$requestType];

        /** @var static $message */
        $message = new $messageClass;
        $message->requestType = $requestType;
        $message->recipientChannel = $recipientChannel;
        $message->wantReply = read_boolean($payload);
        $message->decodeExtraData($payload);

        return $message;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_CHANNEL_REQUEST;
    }
}
