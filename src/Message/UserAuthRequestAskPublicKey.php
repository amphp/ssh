<?php

namespace Amp\Ssh\Message;

class UserAuthRequestAskPublicKey extends UserAuthRequest {
    public $keyAlgorithm;

    public $keyBlob;

    protected function extraEncode(): string {
        return \pack(
            'CNa*Na*',
            0,
            \strlen($this->keyAlgorithm),
            $this->keyAlgorithm,
            \strlen($this->keyBlob),
            $this->keyBlob
        );
    }
}
