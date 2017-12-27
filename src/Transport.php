<?php

namespace Amp\SSH;

use function Amp\call;
use Amp\Promise;
use Amp\Socket\Socket;
use Amp\SSH\Encryption\Aes128Ctr;
use Amp\SSH\Encryption\Decryption;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Encryption\None;
use Amp\SSH\KeyExchange\Curve25519Sha256;
use Amp\SSH\Mac\Hash;
use Amp\SSH\Message\KeyExchangeCurveReply;
use Amp\SSH\Message\KeyExchangeInit;
use Amp\SSH\Message\Message;
use Amp\SSH\Message\NewKeys;

class Transport
{
    private $identification = 'SSH-2.0-AmpSSH_0.1';

    /** @var Encryption */
    private $encryption;

    /** @var Decryption */
    private $decryption;

    private $negotiator;

    /** @var string */
    private $sessionId;

    private $socket;

    public function __construct(Socket $socket)
    {
        $this->encryption = new None();
        $this->decryption = new None();
        $this->negotiator = new Negotiator();
        $this->negotiator->addKeyExchange(new Curve25519Sha256());
        $this->negotiator->addEncryption(new Aes128Ctr());
        $this->negotiator->addDecryption(new Aes128Ctr());
        $this->negotiator->addMac(new Hash('sha256', 'hmac-sha2-256', 32));
        $this->socket = $socket;
    }

    public function initialize()
    {
        return call(function () {
            /*
            When the connection has been established, both sides MUST send an
            identification string.  This identification string MUST be

            SSH-protoversion-softwareversion SP comments CR LF
            */
            yield $this->socket->write($this->identification . "\r\n");

            /*
            The server MAY send other lines of data before sending the version
            string.  Each line SHOULD be terminated by a Carriage Return and Line
            Feed.  Such lines MUST NOT begin with "SSH-", and SHOULD be encoded
            in ISO-10646 UTF-8 [RFC3629] (language is not specified).  Clients
            MUST be able to process such lines.  Such lines MAY be silently
            ignored, or MAY be displayed to the client user.
            */
            $identification = false;
            $readed = '';

            while ($identification === false) {
                $readed .= yield $this->socket->read();

                if (($crlfpos = strpos($readed, "\r\n")) !== false) {
                    $line = substr($readed, 0, $crlfpos);

                    if (strpos($line, 'SSH') === 0) {
                        $identification = $line;
                    }

                    $readed = substr($readed, $crlfpos + 2);
                }
            }

            $binaryPacketHandler = new BinaryPacketHandler($this->socket, $readed);

            return yield $this->negotation($binaryPacketHandler, $identification);
        });
    }

    private function negotation(BinaryPacketHandler $binaryPacketHandler, string $serverIdentification)
    {
        return call(function () use($binaryPacketHandler, $serverIdentification) {
            /*
            Key exchange will begin immediately after sending this identifier.
            All packets following the identification string SHALL use the binary
            packet protocol,
            */

            $serverKexPayload = yield $binaryPacketHandler->read();

            $serverKex = KeyExchangeInit::decode($serverKexPayload);
            $clientKex = $this->negotiator->createKeyExchange();
            $clientKexPayload = $clientKex->encode();

            yield $binaryPacketHandler->write($clientKexPayload);

            // Negotiate
            $kex = $this->negotiator->getKeyExchange($serverKex, $clientKex);
            $encrypt = $this->negotiator->getEncrypt($serverKex, $clientKex);
            $decrypt = $this->negotiator->getDecrypt($serverKex, $clientKex);
            $encryptMac = $this->negotiator->getEncryptMac($serverKex, $clientKex);
            $decryptMac = $this->negotiator->getDecryptMac($serverKex, $clientKex);

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

            var_dump($this->identification);
            var_dump($serverIdentification);
            var_dump(bin2hex($clientKexPayload));
            var_dump(bin2hex($serverKexPayload));
            var_dump(bin2hex($kex->getHostKey($exchangeReceive)));
            var_dump(bin2hex($kex->getEBytes($exchangeSend)));
            var_dump(bin2hex($kex->getFBytes($exchangeReceive)));
            var_dump(bin2hex($key));
            $exchangeHash = pack(
                'Na*Na*Na*Na*Na*Na*Na*Na*',
                \strlen($this->identification),
                $this->identification,
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

            if ($this->sessionId === null) {
                $this->sessionId = $exchangeHash;
            }

            $serverHostKeyFormat = $this->negotiator->getServerHostKey($serverKex, $clientKex);

            if ($serverHostKeyFormat !== $exchangeReceive->signatureFormat || $serverHostKeyFormat !== $exchangeReceive->hostKeyFormat) {
                throw new \RuntimeException('Bad protocol negotiated');
            }

            yield $binaryPacketHandler->write((new NewKeys())->encode());
            NewKeys::decode(yield $binaryPacketHandler->read());

            $key = pack('Na*', \strlen($key), $key);

            $key = base64_decode("AAABAE3HFyfJ2YFA6QydiYZoQyt4bU7feHj02aGYysYsOsUuJqaFZxOqWqewntEmFtVQK2Af5MwcdqjCV9gWAnRzyxBBZbvjzwROAKdk/CMR3HK/kmbeOZc4zX+lqsKZx9soiNTlE+zbfDo1L2qe2nTkr7fbS8gPyMLx/3eiGcsaJVMo7aWQXXhEhciuBz1s1jVhw1F46s4c9vYUdWoEztA3yvRtNrf7rxG3uN11hlTFaJ9SLvysMUqxmm86l8BOU9xS03XujTohY0GwyAEQod1A20rbgZrP161pa7tddGLq57iEDNBfb5Q2CrWUKgnlm022Ob9+eynbGt/gYePhOcc3GxI=");
            $exchangeHash = $this->sessionId = base64_decode("Ga6ggk7D0AGkkK30hf5MTAqVQRk=");

//            var_dump(base64_encode($key));

            $createDerivationKey = function ($type, $length) use ($kex, $key, $exchangeHash) {
                $derivation = $kex->hash($key . $exchangeHash . $type . $this->sessionId);

                while ($length > \strlen($derivation)) {
                    $derivation .= $kex->hash($key . $exchangeHash . $derivation);
                }

                return substr($derivation, 0, $length);
            };

            $test = $createDerivationKey('A', $encrypt->getBlockSize());

            // o5orYwaHlDgVwUcsaO4Wqg==
            var_dump(base64_encode($test));

            $encrypt->resetEncrypt(
                $createDerivationKey('C', $encrypt->getBlockSize()),
                $createDerivationKey('A', $encrypt->getBlockSize())
            );

            $decrypt->resetDecrypt(
                $createDerivationKey('D', $encrypt->getBlockSize()),
                $createDerivationKey('B', $decrypt->getBlockSize())
            );

            $encryptMac->setKey($createDerivationKey('E', $encryptMac->getLength()));
            $decryptMac->setKey($createDerivationKey('F', $decryptMac->getLength()));

            $binaryPacketHandler->setEncryption($encrypt);
            $binaryPacketHandler->setDecryption($decrypt);
            $binaryPacketHandler->setEncryptMac($encryptMac);
            $binaryPacketHandler->setDecryptMac($decryptMac);

            return $binaryPacketHandler;
        });
    }
}
