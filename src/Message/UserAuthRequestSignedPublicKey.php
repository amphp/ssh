<?php

namespace Amp\Ssh\Message;

class UserAuthRequestSignedPublicKey extends UserAuthRequest {
    public $keyAlgorithm;

    public $keyBlob;

    public $signature;

    protected function extraEncode(): string {
        if (null === $this->signature) {
            return \pack(
                'CNa*Na*',
                1,
                \strlen($this->keyAlgorithm),
                $this->keyAlgorithm,
                \strlen($this->keyBlob),
                $this->keyBlob
            );
        }

        return \pack(
            'CNa*Na*Na*',
            1,
            \strlen($this->keyAlgorithm),
            $this->keyAlgorithm,
            \strlen($this->keyBlob),
            $this->keyBlob,
            \strlen($this->signature),
            $this->signature
        );
    }
}
