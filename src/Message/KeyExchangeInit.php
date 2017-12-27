<?php

namespace Amp\SSH\Message;

class KeyExchangeInit implements Message
{
    public $cookie;
    public $kexAlgorithms = [];
    public $serverHostKeyAlgorithms = [];
    public $encryptionAlgorithmsClientToServer = [];
    public $encryptionAlgorithmsServerToClient = [];
    public $macAlgorithmsClientToServer = [];
    public $macAlgorithmsServerToClient = [];
    public $compressionAlgorithmsClientToServer = [];
    public $compressionAlgorithmsServerToClient = [];
    public $languagesClientToServer = [];
    public $languagesServerToClient = [];
    public $firstKexPacketFollow = false;

    public function encode(): string
    {
        if (null === $this->cookie) {
            $this->cookie = \random_bytes(16);
        }

        $nameListKex = $this->toNameList($this->kexAlgorithms);
        $nameListServerHostKey = $this->toNameList($this->serverHostKeyAlgorithms);
        $nameListEncryptionCToS = $this->toNameList($this->encryptionAlgorithmsClientToServer);
        $nameListEncryptionSToC = $this->toNameList($this->encryptionAlgorithmsServerToClient);
        $nameListMacCToS = $this->toNameList($this->macAlgorithmsClientToServer);
        $nameListMacSToC = $this->toNameList($this->macAlgorithmsServerToClient);
        $nameListCompressionCToS = $this->toNameList($this->compressionAlgorithmsClientToServer);
        $nameListCompressionSToC = $this->toNameList($this->compressionAlgorithmsServerToClient);
        $nameListLangCToS = $this->toNameList($this->languagesClientToServer);
        $nameListLangSToC = $this->toNameList($this->languagesServerToClient);

        return pack(
            'Ca*Na*Na*Na*Na*Na*Na*Na*Na*Na*Na*CN',
            self::getNumber(),
            $this->cookie,
            \strlen($nameListKex),
            $nameListKex,
            \strlen($nameListServerHostKey),
            $nameListServerHostKey,
            \strlen($nameListEncryptionCToS),
            $nameListEncryptionCToS,
            \strlen($nameListEncryptionSToC),
            $nameListEncryptionSToC,
            \strlen($nameListMacCToS),
            $nameListMacCToS,
            \strlen($nameListMacSToC),
            $nameListMacSToC,
            \strlen($nameListCompressionCToS),
            $nameListCompressionCToS,
            \strlen($nameListCompressionSToC),
            $nameListCompressionSToC,
            \strlen($nameListLangCToS),
            $nameListLangCToS,
            \strlen($nameListLangSToC),
            $nameListLangSToC,
            $this->firstKexPacketFollow,
            0
        );
    }

    private function toNameList($value)
    {
        return implode(',', $value);
    }

    public static function decode(string $payload)
    {
        $byte = unpack('C', $payload)[1];

        if ($byte !== self::getNumber()) {
            throw new \RuntimeException('Decoding wrong message');
        }

        $keyExchangeInit = new self();
        $keyExchangeInit->cookie = substr($payload, 1, 16);
        [$keyExchangeInit->kexAlgorithms, $payload] = self::decodeNameList(substr($payload, 17));
        [$keyExchangeInit->serverHostKeyAlgorithms, $payload] = self::decodeNameList($payload);
        [$keyExchangeInit->encryptionAlgorithmsClientToServer, $payload] = self::decodeNameList($payload);
        [$keyExchangeInit->encryptionAlgorithmsServerToClient, $payload] = self::decodeNameList($payload);
        [$keyExchangeInit->macAlgorithmsClientToServer, $payload] = self::decodeNameList($payload);
        [$keyExchangeInit->macAlgorithmsServerToClient, $payload] = self::decodeNameList($payload);
        [$keyExchangeInit->compressionAlgorithmsClientToServer, $payload] = self::decodeNameList($payload);
        [$keyExchangeInit->compressionAlgorithmsServerToClient, $payload] = self::decodeNameList($payload);
        [$keyExchangeInit->languagesClientToServer, $payload] = self::decodeNameList($payload);
        [$keyExchangeInit->languagesServerToClient, $payload] = self::decodeNameList($payload);

        $keyExchangeInit->firstKexPacketFollow = unpack('C', $payload)[1];

        return $keyExchangeInit;
    }

    private static function decodeNameList($payload)
    {
        $length = unpack('N', $payload)[1];
        $nameListStr = substr($payload, 4, $length);

        return [
            empty($nameListStr) ? [] : explode(',', $nameListStr),
            substr($payload, $length + 4)
        ];
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_KEXINIT;
    }
}
