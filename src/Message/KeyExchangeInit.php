<?php

namespace Amp\SSH\Message;

use function Amp\SSH\Transport\read_boolean;
use function Amp\SSH\Transport\read_byte;
use function Amp\SSH\Transport\read_bytes;
use function Amp\SSH\Transport\read_namelist;

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
        read_byte($payload);

        $keyExchangeInit = new static();
        $keyExchangeInit->cookie = read_bytes($payload, 16);
        $keyExchangeInit->kexAlgorithms = read_namelist($payload);
        $keyExchangeInit->serverHostKeyAlgorithms = read_namelist($payload);
        $keyExchangeInit->encryptionAlgorithmsClientToServer = read_namelist($payload);
        $keyExchangeInit->encryptionAlgorithmsServerToClient = read_namelist($payload);
        $keyExchangeInit->macAlgorithmsClientToServer = read_namelist($payload);
        $keyExchangeInit->macAlgorithmsServerToClient = read_namelist($payload);
        $keyExchangeInit->compressionAlgorithmsClientToServer = read_namelist($payload);
        $keyExchangeInit->compressionAlgorithmsServerToClient = read_namelist($payload);
        $keyExchangeInit->languagesClientToServer = read_namelist($payload);
        $keyExchangeInit->languagesServerToClient = read_namelist($payload);
        $keyExchangeInit->firstKexPacketFollow = read_boolean($payload);

        return $keyExchangeInit;
    }

    public static function getNumber(): int
    {
        return self::SSH_MSG_KEXINIT;
    }
}
