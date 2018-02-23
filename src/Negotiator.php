<?php

namespace Amp\Ssh;

use Amp\Promise;
use Amp\Ssh\Encryption\Aes;
use Amp\Ssh\Encryption\Decryption;
use Amp\Ssh\Encryption\Encryption;
use Amp\Ssh\KeyExchange\Curve25519Sha256;
use Amp\Ssh\KeyExchange\KeyExchange;
use Amp\Ssh\Mac\Hash;
use Amp\Ssh\Mac\Mac;
use Amp\Ssh\Message\KeyExchangeInit;
use Amp\Ssh\Message\Message;
use Amp\Ssh\Message\NewKeys;
use Amp\Ssh\Transport\BinaryPacketHandler;
use function Amp\call;

/**
 * Negotiate algorithms to use for the ssh connection
 */
class Negotiator {
    /** @var Decryption[] */
    private $decryptions = [];

    /** @var Encryption[] */
    private $encryptions = [];

    /** @var KeyExchange[] */
    private $keyExchanges = [];

    /** @var Mac[] */
    private $macs = [];

    private $sessionId;

    private function addDecryption(Decryption $decryption): void {
        $this->decryptions[$decryption->getName()] = $decryption;
    }

    private function addEncryption(Encryption $encryption): void {
        $this->encryptions[$encryption->getName()] = $encryption;
    }

    private function addKeyExchange(KeyExchange $keyExchange): void {
        $this->keyExchanges[$keyExchange->getName()] = $keyExchange;
    }

    private function addMac(Mac $mac): void {
        $this->macs[$mac->getName()] = $mac;
    }

    public function getSessionId(): string {
        return $this->sessionId;
    }

    public function negotiate(BinaryPacketHandler $binaryPacketHandler, string $serverIdentification, string $clientIdentification): Promise {
        return call(function () use ($binaryPacketHandler, $serverIdentification, $clientIdentification) {
            /*
            Key exchange will begin immediately after sending this identifier.
            All packets following the identification string SHALL use the binary
            packet protocol,
            */

            $serverKex = yield $binaryPacketHandler->read();

            if (!$serverKex instanceof KeyExchangeInit) {
                throw new \RuntimeException('Invalid packet');
            }

            $clientKex = $this->createKeyExchange();
            yield $binaryPacketHandler->write($clientKex);

            // Negotiate
            $kex = $this->getKeyExchange($serverKex, $clientKex);
            $encrypt = $this->getEncrypt($serverKex, $clientKex);
            $decrypt = $this->getDecrypt($serverKex, $clientKex);
            $encryptMac = $this->getEncryptMac($serverKex, $clientKex);
            $decryptMac = $this->getDecryptMac($serverKex, $clientKex);

            /** @var Message $exchangeSend */
            /** @var Message $exchangeReceive */
            [$key, $exchangeSend, $exchangeReceive] = yield $kex->exchange($binaryPacketHandler);

            /*
            The hash H is computed as the HASH hash of the concatenation of the
            following:

                string    V_C, the client's identification string (CR and LF
                        excluded)
                string    V_S, the server's identification string (CR and LF
                        excluded)
                string    I_C, the payload of the client's SSH_MSG_KEXINIT
                string    I_S, the payload of the server's SSH_MSG_KEXINIT
                string    K_S, the host key
                mpint     e, exchange value sent by the client
                mpint     f, exchange value sent by the server
                mpint     K, the shared secret
             */

            $clientKexPayload = $clientKex->encode();
            $serverKexPayload = $serverKex->encode();

            $exchangeHash = \pack(
                'Na*Na*Na*Na*Na*Na*Na*Na*',
                \strlen($clientIdentification),
                $clientIdentification,
                \strlen($serverIdentification),
                $serverIdentification,
                \strlen($clientKexPayload),
                $clientKexPayload,
                \strlen($serverKexPayload),
                $serverKexPayload,
                \strlen($kex->getHostKey($exchangeReceive)),
                $kex->getHostKey($exchangeReceive),
                \strlen($kex->getEBytes($exchangeSend)),
                $kex->getEBytes($exchangeSend),
                \strlen($kex->getFBytes($exchangeReceive)),
                $kex->getFBytes($exchangeReceive),
                \strlen($key),
                $key
            );

            $exchangeHash = $kex->hash($exchangeHash);

            if ($this->sessionId === null) {
                $this->sessionId = $exchangeHash;
            }

            $serverHostKeyFormat = $this->getServerHostKey($serverKex, $clientKex);

            if ($serverHostKeyFormat !== $exchangeReceive->signatureFormat || $serverHostKeyFormat !== $exchangeReceive->hostKeyFormat) {
                throw new \RuntimeException('Bad protocol negotiated');
            }

            yield $binaryPacketHandler->write(new NewKeys());
            yield $binaryPacketHandler->read();

            $key = \pack('Na*', \strlen($key), $key);

            $createDerivationKey = function ($type, $length) use ($kex, $key, $exchangeHash) {
                $derivation = $kex->hash($key . $exchangeHash . $type . $this->sessionId);

                while ($length > \strlen($derivation)) {
                    $derivation .= $kex->hash($key . $exchangeHash . $derivation);
                }

                return \substr($derivation, 0, $length);
            };

            $encrypt->resetEncrypt(
                $createDerivationKey('C', $encrypt->getKeySize()),
                $createDerivationKey('A', $encrypt->getBlockSize())
            );

            $decrypt->resetDecrypt(
                $createDerivationKey('D', $encrypt->getKeySize()),
                $createDerivationKey('B', $decrypt->getBlockSize())
            );

            $encryptMac->setKey($createDerivationKey('E', $encryptMac->getLength()));
            $decryptMac->setKey($createDerivationKey('F', $decryptMac->getLength()));

            $binaryPacketHandler->updateEncryption($encrypt, $encryptMac);
            $binaryPacketHandler->updateDecryption($decrypt, $decryptMac);

            return $binaryPacketHandler;
        });
    }

    private function getDecrypt(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): Decryption {
        $decrypt = \current(\array_intersect(
            $serverKex->encryptionAlgorithmsServerToClient,
            $clientKex->encryptionAlgorithmsServerToClient
        ));

        return $this->decryptions[$decrypt];
    }

    private function getEncrypt(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): Encryption {
        $encrypt = \current(\array_intersect(
            $serverKex->encryptionAlgorithmsClientToServer,
            $clientKex->encryptionAlgorithmsClientToServer
        ));

        return $this->encryptions[$encrypt];
    }

    private function getKeyExchange(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): KeyExchange {
        $keyExchangeName = \current(\array_intersect(
            $serverKex->kexAlgorithms,
            $clientKex->kexAlgorithms
        ));

        return $this->keyExchanges[$keyExchangeName];
    }

    private function getServerHostKey(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex) {
        $serverHostKey = \current(\array_intersect(
            $serverKex->serverHostKeyAlgorithms,
            $clientKex->serverHostKeyAlgorithms
        ));

        return $serverHostKey;
    }

    private function getDecryptMac(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): Mac {
        $mac = \current(\array_intersect(
            $serverKex->macAlgorithmsServerToClient,
            $clientKex->macAlgorithmsServerToClient
        ));

        return clone $this->macs[$mac];
    }

    private function getEncryptMac(KeyExchangeInit $serverKex, KeyExchangeInit $clientKex): Mac {
        $mac = \current(\array_intersect(
            $serverKex->macAlgorithmsClientToServer,
            $clientKex->macAlgorithmsClientToServer
        ));

        return clone $this->macs[$mac];
    }

    private function createKeyExchange(): KeyExchangeInit {
        $clientKex = new KeyExchangeInit();
        $clientKex->cookie = \random_bytes(16);
        $clientKex->kexAlgorithms = \array_keys($this->keyExchanges);
        $clientKex->serverHostKeyAlgorithms = [
            'ssh-rsa', // RECOMMENDED  sign   Raw RSA Key
            'ssh-dss'  // REQUIRED     sign   Raw DSS Key
        ];
        $clientKex->encryptionAlgorithmsClientToServer = \array_keys($this->encryptions);
        $clientKex->encryptionAlgorithmsServerToClient = \array_keys($this->decryptions);
        $clientKex->macAlgorithmsServerToClient = \array_keys($this->macs);
        $clientKex->macAlgorithmsClientToServer = \array_keys($this->macs);
        $clientKex->compressionAlgorithmsServerToClient = $clientKex->compressionAlgorithmsClientToServer = [
            'none',
        ];

        return $clientKex;
    }

    public static function create() {
        $negotiator = new static();
        $negotiator->addKeyExchange(new Curve25519Sha256());

        foreach (Aes::create() as $algorithm) {
            $negotiator->addEncryption($algorithm);
        }

        foreach (Aes::create() as $algorithm) {
            $negotiator->addDecryption($algorithm);
        }

        $negotiator->addMac(new Hash('sha256', 'hmac-sha2-256', 32));
        $negotiator->addMac(new Hash('sha1', 'hmac-sha1', 20));

        return $negotiator;
    }
}
