<?php

namespace Amp\Ssh\Message;

use function Amp\Ssh\Transport\read_boolean;
use function Amp\Ssh\Transport\read_byte;
use function Amp\Ssh\Transport\read_string;
use function Amp\Ssh\Transport\read_uint32;

/**
 * @internal
 */
abstract class ChannelRequest implements Message {
    const TYPE_PTY = 'pty-req';
    const TYPE_X11_FORWARDING = 'x11-req';
    const TYPE_ENV = 'env';
    const TYPE_SHELL = 'shell';
    const TYPE_EXEC = 'exec';
    const TYPE_SUBSYSTEM = 'subsystem';
    const TYPE_WINDOW_CHANGE = 'subsystem';
    const TYPE_SIGNAL = 'signal';
    const TYPE_EXIT_STATUS = 'exit-status';
    const TYPE_EXIT_SIGNAL = 'exit-signal';

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

    public function encode(): string {
        return \pack(
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

    public static function decode(string $payload) {
        read_byte($payload);
        $recipientChannel = read_uint32($payload);
        $requestType = read_string($payload);

        if (!\array_key_exists($requestType, self::$typeMapping)) {
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

    public static function getNumber(): int {
        return self::SSH_MSG_CHANNEL_REQUEST;
    }
}
