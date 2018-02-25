<?php

namespace Amp\Ssh\Message;

abstract class UserAuthRequest implements Message {
    const TYPE_PASSWORD = 'password';
    const TYPE_PUBLIC_KEY = 'publickey';
    const TYPE_NONE = 'publickey';

    public $username;
    public $authType = self::TYPE_NONE;
    public $serviceName = 'ssh-connection';

    public function encode(): string {
        return \pack(
            'CNa*Na*Na*a*',
            self::getNumber(),
            \strlen($this->username),
            $this->username,
            \strlen($this->serviceName),
            $this->serviceName,
            \strlen($this->authType),
            $this->authType,
            $this->extraEncode()
        );
    }

    abstract protected function extraEncode(): string;

    public static function decode(string $payload) {
        return new static();
    }

    public static function getNumber(): int {
        return self::SSH_MSG_USERAUTH_REQUEST;
    }
}
