<?php

declare(strict_types=1);

namespace Amp\SSH\Authentication;

use function Amp\call;
use Amp\Promise;
use Amp\SSH\BinaryPacketHandler;
use Amp\SSH\Message\Message;
use Amp\SSH\Message\ServiceAccept;
use Amp\SSH\Message\ServiceRequest;
use Amp\SSH\Message\UserAuthRequest;

class UsernamePassword implements Authentication
{
    private $username;
    private $password;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function authenticate(BinaryPacketHandler $binaryPacketHandler): Promise
    {
        return call(function () use($binaryPacketHandler) {
            $authServiceRequest = new ServiceRequest();
            $authServiceRequest->serviceName = 'ssh-userauth';

            yield $binaryPacketHandler->write($authServiceRequest->encode());
            $packet = yield $binaryPacketHandler->read();

            ServiceAccept::decode($packet);

            $userAuthRequest = new UserAuthRequest();
            $userAuthRequest->authType = UserAuthRequest::TYPE_PASSWORD;
            $userAuthRequest->username = $this->username;
            $userAuthRequest->password = $this->password;

            yield $binaryPacketHandler->write($userAuthRequest->encode());
            $packet = yield $binaryPacketHandler->read();
            $type = unpack('C', $packet)[1];

            // @TODO Handle specific message
            return $type === Message::SSH_MSG_USERAUTH_SUCCESS;
        });
    }
}
