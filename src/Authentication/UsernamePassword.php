<?php

namespace Amp\SSH\Authentication;

use Amp\Promise;
use Amp\SSH\Message\ServiceRequest;
use Amp\SSH\Message\UserAuthFailure;
use Amp\SSH\Message\UserAuthRequestPassword;
use Amp\SSH\Transport\BinaryPacketHandler;
use function Amp\call;

class UsernamePassword implements Authentication {
    private $username;
    private $password;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function authenticate(BinaryPacketHandler $binaryPacketHandler, string $sessionId): Promise {
        return call(function () use ($binaryPacketHandler) {
            $authServiceRequest = new ServiceRequest();
            $authServiceRequest->serviceName = 'ssh-userauth';

            yield $binaryPacketHandler->write($authServiceRequest);
            yield $binaryPacketHandler->read();

            $userAuthRequest = new UserAuthRequestPassword();
            $userAuthRequest->authType = UserAuthRequestPassword::TYPE_PASSWORD;
            $userAuthRequest->username = $this->username;
            $userAuthRequest->password = $this->password;

            yield $binaryPacketHandler->write($userAuthRequest);
            $packet = yield $binaryPacketHandler->read();

            if ($packet instanceof UserAuthFailure) {
                throw new \RuntimeException('Authentication failure');
            }

            return $packet;
        });
    }
}
