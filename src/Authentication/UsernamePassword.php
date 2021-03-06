<?php

namespace Amp\Ssh\Authentication;

use function Amp\call;
use Amp\Promise;
use Amp\Ssh\Message\ServiceRequest;
use Amp\Ssh\Message\UserAuthFailure;
use Amp\Ssh\Message\UserAuthRequestPassword;
use Amp\Ssh\Transport\BinaryPacketHandler;

final class UsernamePassword implements Authentication {
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
                throw new AuthenticationFailureException('Authentication failure');
            }

            return $packet;
        });
    }
}
