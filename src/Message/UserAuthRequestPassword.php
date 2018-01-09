<?php

namespace Amp\SSH\Message;

class UserAuthRequestPassword extends UserAuthRequest {
    public $password = '';

    protected function extraEncode(): string {
        return \pack(
            'CNa*',
            0,
            \strlen($this->password),
            $this->password
        );
    }
}
