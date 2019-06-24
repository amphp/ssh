<?php

namespace Amp\Ssh\Tests\Channel;

use Amp\Loop;
use Amp\Ssh\Authentication\UsernamePassword;
use Amp\Ssh\Channel\ChannelException;
use Amp\Ssh\Channel\Session;
use function Amp\Ssh\connect;
use Amp\Ssh\SshResource;
use Amp\Ssh\Tests\NetworkHelper;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase {
    protected function getSsh() {
        return connect('127.0.0.1:2222', new UsernamePassword('root', 'root'));
    }

    /**
     * if connection closed by server then fail dispatcher and all opened request emitters.
     */
    public function testRequestEmitterFailedAfterDisconnect() {
        $this->expectException(ChannelException::class);
        Loop::run(function () {
            $connection = yield $this->getSsh();
            /** @var Session $session */
            $session = $connection->createSession();
            yield $session->open();
            NetworkHelper::disconnect($connection);
            $iterator = $session->getRequestEmitter()->iterate();
            yield $iterator->advance();
        });
    }

    /**
     * if connection  closed by server then fail dispatcher and close all opened data emitters.
     */
    public function testDataEmitterClosedAfterDisconnect() {
        Loop::run(function () {
            $connection = yield $this->getSsh();
            /** @var Session $session */
            $session = $connection->createSession();
            yield $session->open();
            NetworkHelper::disconnect($connection);

            $iterator = $session->getDataEmitter()->iterate();
            $hasNext = yield $iterator->advance();
            $this->assertFalse($hasNext);

            $iterator = $session->getDataExtendedEmitter()->iterate();
            $hasNext = yield $iterator->advance();
            $this->assertFalse($hasNext);
        });
    }

    /**
     * if dispatcher closed then all opened channels from client must stop.
     */
    public function testSessionClosedAfterConnectionClose() {
        Loop::run(function () {
            /** @var SshResource $connection */
            $connection = yield $this->getSsh();
            $session = $connection->createSession();
            yield $session->open();
            $connection->close();

            $iterator = $session->getRequestEmitter()->iterate();

            $hasNext = yield $iterator->advance();
            $this->assertFalse($hasNext);
        });
    }
}
