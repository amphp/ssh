<?php

namespace Amp\Ssh\Tests;

use Amp\Loop;
use Amp\Ssh\Authentication\AuthenticationFailureException;
use Amp\Ssh\Authentication\PublicKey;
use function Amp\Ssh\connect;
use Amp\Ssh\SshResource;
use PHPUnit\Framework\TestCase;

class PublicKeyTest extends TestCase {
    public function testRsaSuccess() {
        Loop::run(function () {
            $authentication = new PublicKey('root', __DIR__ . '/../key_rsa');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());

            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }

    public function testRsaFail() {
        self::expectException(AuthenticationFailureException::class);

        Loop::run(function () {
            $authentication = new PublicKey('root', __DIR__ . '/../invalid_key_rsa');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());

            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }

    public function testRsaNotExistingFile() {
        self::expectException(AuthenticationFailureException::class);

        Loop::run(function () {
            $authentication = new PublicKey('root', __DIR__ . '/../not_existing');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());

            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }

    public function testRsaPassphraseSuccess() {
        Loop::run(function () {
            $authentication = new PublicKey('root', __DIR__ . '/../key_passphrase_rsa', 'passphrase');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());

            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }

    public function testRsaPassphraseFail() {
        self::expectException(AuthenticationFailureException::class);

        Loop::run(function () {
            $authentication = new PublicKey('root', __DIR__ . '/../key_passphrase_rsa', 'bad');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());

            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }

    /**
     * Ecdsa is not supported ATM.
     */
    public function testEcdsa() {
        self::expectException(AuthenticationFailureException::class);

        Loop::run(function () {
            $authentication = new PublicKey('root', __DIR__ . '/../key_ecdsa');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());

            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }
}
