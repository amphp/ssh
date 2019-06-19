<?php

namespace Amp\Ssh\Tests;

use function Amp\call;
use Amp\Loop;
use Amp\Ssh\Authentication\UsernamePassword;
use function Amp\Ssh\connect;
use Amp\Ssh\Shell;
use Amp\Ssh\StatusError;
use PHPUnit\Framework\TestCase;

class ShellTest extends TestCase {
    protected function getSsh() {
        return call(function () {
            $authentication = new UsernamePassword('root', 'root');

            return yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());
        });
    }

    public function testShell() {
        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $shell = new Shell($ssh);

            yield $shell->start();
            yield $shell->getStdin()->write("echo foo; exit\n");

            self::assertTrue($shell->isRunning());

            $exitCode = yield $shell->join();
            $output = '';

            while ($read = yield $shell->getStdout()->read()) {
                $output .= $read;
            }

            self::assertFalse($shell->isRunning());
            self::assertContains('foo', $output);
            self::assertEquals(0, $exitCode);

            yield $ssh->close();
        });
    }

    public function testShellNotStartedOnJoin() {
        self::expectException(StatusError::class);

        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $shell = new Shell($ssh);

            yield $shell->join();
        });
    }

    public function testShellNotStartedOnSignal() {
        self::expectException(StatusError::class);

        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $shell = new Shell($ssh);

            yield $shell->signal(SIGKILL);
        });
    }

    public function testShellAlreadyStarted() {
        self::expectException(StatusError::class);

        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $shell = new Shell($ssh);

            yield $shell->start();
            yield $shell->start();
        });
    }


    public function testShellEnv() {
        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $shell = new Shell($ssh, ['FOO' => 'bar']);

            yield $shell->start();
            self::assertTrue($shell->isRunning());

            yield $shell->getStdin()->write("echo \$FOO; exit\n");

            $exitCode = yield $shell->join();
            $output = '';

            while ($read = yield $shell->getStdout()->read()) {
                $output .= $read;
            }

            self::assertFalse($shell->isRunning());
            self::assertContains('bar', $output);
            self::assertEquals(0, $exitCode);

            $exitCode = yield $shell->join();

            self::assertEquals(0, $exitCode);

            yield $ssh->close();
        });
    }

    public function testStdInZero() {
        Loop::run(function () {
            $sshResource = yield $this->getSsh();

            $shell = new \Amp\Ssh\Shell($sshResource);
            yield $shell->start();


            \Amp\asyncCall(function () use ($shell) {
                yield $shell->join();
            });

            // read greeting from server
            while ($chunk = yield $shell->getStdout()->read()) {
                if (\strpos($chunk, ':~#') !== false) {
                    break;
                }
            }
            // enter 1 in terminal
            yield $shell->getStdin()->write(1);
            $this->assertEquals(1, yield $shell->getStdout()->read());
            // try enter 0
            yield $shell->getStdin()->write(0);
            $this->assertEquals(0, yield \Amp\Promise\timeout($shell->getStdout()->read(), 10));
			yield $sshResource->close();
        });
    }
}
