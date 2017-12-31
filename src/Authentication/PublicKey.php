<?php

namespace Amp\SSH\Authentication;

use function Amp\call;
use function Amp\File\exists;
use function Amp\File\get;
use Amp\Promise;
use Amp\SSH\Message\ServiceRequest;
use Amp\SSH\Message\UserAuthFailure;
use Amp\SSH\Message\UserAuthPkOk;
use Amp\SSH\Message\UserAuthRequest;
use Amp\SSH\Message\UserAuthRequestAskPublicKey;
use Amp\SSH\Message\UserAuthRequestSignedPublicKey;
use Amp\SSH\Transport\BinaryPacketHandler;

class PublicKey implements Authentication
{
    private $privateKeyPath;

    private $username;

    private $passphrase;

    public function __construct(string $username, string $privateKeyPath = '~/.ssh/id_rsa', string $passphrase = '')
    {
        $this->username = $username;
        $this->privateKeyPath = $privateKeyPath;
        $this->passphrase = $passphrase;
    }

    public function authenticate(BinaryPacketHandler $handler, string $sessionId): Promise
    {
        return call(function () use($handler, $sessionId) {
            if (!yield exists($this->privateKeyPath)) {
                throw new \RuntimeException('Private key does not exist at path: ' . $this->privateKeyPath);
            }

            $fileContent = yield get($this->privateKeyPath);
            $key = \openssl_get_privatekey($fileContent, $this->passphrase);
            $details = \openssl_pkey_get_details($key);

            if ($details['type'] === OPENSSL_KEYTYPE_RSA) {
                return yield $this->rsa($handler, $key, $details, $sessionId);
            }

            throw new \RuntimeException('Private Key Format is not supported.');
        });
    }

    protected function rsa(BinaryPacketHandler $handler, $key, array $privateKeyInfo, string $sessionId): Promise
    {
        return call(function () use($handler, $key, $privateKeyInfo, $sessionId) {
            $authServiceRequest = new ServiceRequest();
            $authServiceRequest->serviceName = 'ssh-userauth';

            yield $handler->write($authServiceRequest);
            yield $handler->read();

            $e = $privateKeyInfo['rsa']['e'];
            $n = $privateKeyInfo['rsa']['n'];

            if (\ord($e[0]) & 0x80) {
                $e = \chr(0) . $e;
            }
            if (\ord($n[0]) & 0x80) {
                $n = \chr(0) . $n;
            }

            $publickey = pack(
                'Na*Na*Na*',
                \strlen('ssh-rsa'),
                'ssh-rsa',
                \strlen($e),
                $e,
                \strlen($n),
                $n
            );

            $request = new UserAuthRequestAskPublicKey();
            $request->username = $this->username;
            $request->authType = UserAuthRequest::TYPE_PUBLIC_KEY;
            $request->keyAlgorithm = 'ssh-rsa';
            $request->keyBlob = $publickey;

            yield $handler->write($request);
            $package = yield $handler->read();

            if (!$package instanceof UserAuthPkOk) {
                throw new \RuntimeException('Authentication Failure');
            }

            $signatureRequest = new UserAuthRequestSignedPublicKey();
            $signatureRequest->username = $this->username;
            $signatureRequest->authType = UserAuthRequest::TYPE_PUBLIC_KEY;
            $signatureRequest->keyAlgorithm = 'ssh-rsa';
            $signatureRequest->keyBlob = $publickey;

            $signatureRaw = pack(
                'Na*a*',
                \strlen($sessionId),
                $sessionId,
                $signatureRequest->encode()
            );

            \openssl_sign($signatureRaw, $signature, $key);
            $signature = pack('Na*Na*', \strlen('ssh-rsa'), 'ssh-rsa', \strlen($signature), $signature);

            $signatureRequest->signature = $signature;

            yield $handler->write($signatureRequest);
            $packet = yield $handler->read();

            if ($packet instanceof UserAuthFailure) {
                throw new \RuntimeException('Authentication failure');
            }

            return $packet;
        });
    }
}