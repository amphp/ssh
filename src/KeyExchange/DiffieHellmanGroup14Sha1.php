<?php

namespace Amp\Ssh\KeyExchange;


use function Amp\call;
use Amp\Promise;
use Amp\Ssh\Message\KeyExchangeCurveInit;
use Amp\Ssh\Message\KeyExchangeCurveReply;
use Amp\Ssh\Message\Message;
use Amp\Ssh\Transport\BinaryPacketHandler;

/**
 * @internal
 */
class DiffieHellmanGroup14Sha1 implements KeyExchange
{
    const PRIME = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
    '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
    '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
    'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
    '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
    '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
    'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
    '3995497CEA956AE515D2261898FA051015728E5A8AACAA68FFFFFFFFFFFFFFFF';

    public function getName(): string
    {
        return 'diffie-hellman-group14-sha1';
    }

    public function exchange(BinaryPacketHandler $handler): Promise
    {
        return call(function () use ($handler) {

            $dhKey = openssl_pkey_new([
                'dh' => [
                    'p' => hex2bin(self::PRIME),
                    'g' => \chr(2),
                ]
            ]);
            $details = openssl_pkey_get_details($dhKey);
            $eBytes = $details['dh']['pub_key'];

            $message = new KeyExchangeCurveInit();
            $message->exchange = $this->twosComplement($eBytes);

            yield $handler->write($message);
            $packet = yield $handler->read();

            if (!$packet instanceof KeyExchangeCurveReply) {
                throw new \RuntimeException('Invalid reply');
            }

            $key = openssl_dh_compute_key($packet->fBytes, $dhKey);

            \sodium_memzero($details['dh']['priv_key']);
            unset($details);
            unset($dhKey);

            return [$this->twosComplement($key), $message, $packet];
        });
    }

    private function twosComplement($data)
    {
        return (\ord($data[0]) & 0x80)
            ? \chr(0) . $data
            : $data;

    }

    public function hash(string $payload): string
    {
        return hash('sha1', $payload, true);
    }

    public function getEBytes(Message $message)
    {
        if (!$message instanceof KeyExchangeCurveInit) {
            throw new \RuntimeException();
        }

        return $message->exchange;
    }

    public function getFBytes(Message $message)
    {
        if (!$message instanceof KeyExchangeCurveReply) {
            throw new \RuntimeException();
        }

        return $message->fBytes;
    }

    public function getHostKey(Message $message)
    {
        if (!$message instanceof KeyExchangeCurveReply) {
            throw new \RuntimeException();
        }

        return $message->hostKey;
    }

}