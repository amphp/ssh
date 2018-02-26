<?php

namespace Amp\Ssh\Tests;

use Amp\Loop;
use Amp\Ssh\Authentication\AuthenticationFailureException;
use Amp\Ssh\Authentication\UsernamePassword;
use Amp\Ssh\SSHResource;
use PHPUnit\Framework\TestCase;
use function Amp\Ssh\connect;

class UsernamePasswordTest extends TestCase {
    public function testSuccess() {
        Loop::run(function () {
            $authentication = new UsernamePassword('root', 'root');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());

            self::assertInstanceOf(SSHResource::class, $sshResource);
        });
    }

    public function testFail() {
        self::expectException(AuthenticationFailureException::class);

        Loop::run(function () {
            $authentication = new UsernamePassword('root', 'bad');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());

            self::assertInstanceOf(SSHResource::class, $sshResource);
        });
    }
}
