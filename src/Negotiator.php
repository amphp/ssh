<?php

namespace Amp\SSH;

use Amp\SSH\Encryption\Decryption;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\KeyExchange\KeyExchange;
use Amp\SSH\Mac\Mac;
use Amp\SSH\Message\KeyExchangeInit;

class Negotiator
{
    /** @var Decryption[] */
    private $decryptions = [];

    /** @var Encryption[] */
    private $encryptions = [];

    /** @var KeyExchange[] */
    private $keyExchanges = [];

    /** @var Mac[] */
    private $macs = [];

    public function addDecryption(Decryption $decryption): void
    {
        $this->decryptions[$decryption->getName()] = $decryption;
    }

    public function addEncryption(Encryption $encryption): void
    {
        $this->encryptions[$encryption->getName()] = $encryption;
    }

    public function addKeyExchange(KeyExchange $keyExchange): void
    {
        $this->keyExchanges[$keyExchange->getName()] = $keyExchange;
    }

    public function addMac(Mac $mac): void
    {
        $this->macs[$mac->getName()] = $mac;
    }

    public function getDecrypt(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): Decryption
    {
        $decrypt = current(array_intersect(
            $serverKex->encryptionAlgorithmsServerToClient,
            $clientKex->encryptionAlgorithmsServerToClient
        ));

        return $this->decryptions[$decrypt];
    }

    public function getEncrypt(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): Encryption
    {
        $encrypt = current(array_intersect(
            $serverKex->encryptionAlgorithmsClientToServer,
            $clientKex->encryptionAlgorithmsClientToServer
        ));

        return $this->encryptions[$encrypt];
    }

    public function getKeyExchange(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): KeyExchange
    {
        $keyExchangeName = current(array_intersect(
            $serverKex->kexAlgorithms,
            $clientKex->kexAlgorithms
        ));

        return $this->keyExchanges[$keyExchangeName];
    }

    public function getServerHostKey(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex)
    {
        $serverHostKey = current(array_intersect(
            $serverKex->serverHostKeyAlgorithms,
            $clientKex->serverHostKeyAlgorithms
        ));

        return $serverHostKey;
    }

    public function getDecryptMac(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): Mac
    {
        $mac = current(array_intersect(
            $serverKex->macAlgorithmsServerToClient,
            $clientKex->macAlgorithmsServerToClient
        ));

        return $this->macs[$mac];
    }

    public function getEncryptMac(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): Mac
    {
        $mac = current(array_intersect(
            $serverKex->macAlgorithmsClientToServer,
            $clientKex->macAlgorithmsClientToServer
        ));

        return $this->macs[$mac];
    }

    public function createKeyExchange(): KeyExchangeInit
    {
        $clientKex = new KeyExchangeInit();
        $clientKex->cookie = \random_bytes(16);
        $clientKex->kexAlgorithms = array_keys($this->keyExchanges);
        $clientKex->serverHostKeyAlgorithms = [
            'ssh-rsa', // RECOMMENDED  sign   Raw RSA Key
            'ssh-dss'  // REQUIRED     sign   Raw DSS Key
        ];
        $clientKex->encryptionAlgorithmsClientToServer = array_keys($this->encryptions);
        $clientKex->encryptionAlgorithmsServerToClient = array_keys($this->decryptions);
        $clientKex->macAlgorithmsServerToClient = array_keys($this->macs);
        $clientKex->macAlgorithmsClientToServer = array_keys($this->macs);
        $clientKex->compressionAlgorithmsServerToClient = $clientKex->compressionAlgorithmsClientToServer = [
            'none',
        ];

        return $clientKex;
    }
}
