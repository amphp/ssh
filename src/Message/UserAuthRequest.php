<?php

declare(strict_types=1);

namespace Amp\SSH\Message;

class UserAuthRequest implements Message
{
    public const TYPE_PASSWORD = 'password';
    public const TYPE_PUBLIC_KEY = 'publickey';
    public const TYPE_NONE = 'publickey';

    public $username;
    public $authType = self::TYPE_NONE;
    public $password = '';

    public function encode(): string
    {
        return pack(
            'CNa*Na*Na*CNa*',
            self::getNumber(),
            \strlen($this->username),
            $this->username,
            \strlen('ssh-connection'),
            'ssh-connection',
            \strlen($this->authType),
            $this->authType,
            0,
            \strlen($this->password),
            $this->password
        );
    }

    public static function decode(string $payload)
    {
        return new static();
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_USERAUTH_REQUEST;
    }
}
