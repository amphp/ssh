<?php

namespace Amp\Ssh\Tests;

use function Amp\call;
use Amp\Loop;
use Amp\Ssh\Authentication\UsernamePassword;
use Amp\Ssh\Channel\ChannelException;
use Amp\Ssh\Channel\SessionEnvException;
use function Amp\Ssh\connect;
use Amp\Ssh\Process;
use Amp\Ssh\SshResource;
use Amp\Ssh\StatusError;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase {
    protected function getSsh() {
        return call(function () {
            $authentication = new UsernamePassword('root', 'root');

            return yield connect('127.0.0.1:2222', $authentication, LoggerTest::get());
        });
    }

    public function testProcess() {
        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $process = new Process($ssh, 'echo foo');

            yield $process->start();

            self::assertTrue($process->isRunning());

            $exitCode = yield $process->join();
            $output = '';

            while ($read = yield $process->getStdout()->read()) {
                $output .= $read;
            }

            self::assertFalse($process->isRunning());
            self::assertEquals("foo\n", $output);
            self::assertEquals(0, $exitCode);

            yield $ssh->close();
        });
    }

    public function testProcessNotStartedOnJoin() {
        self::expectException(StatusError::class);

        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $process = new Process($ssh, 'echo foo');

            yield $process->join();
        });
    }

    public function testProcessNotStartedOnSignal() {
        self::expectException(StatusError::class);

        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $process = new Process($ssh, 'echo foo');

            yield $process->signal(SIGKILL);
        });
    }

    public function testProcessAlreadyStarted() {
        self::expectException(StatusError::class);

        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $process = new Process($ssh, 'echo foo');

            yield $process->start();
            yield $process->start();
        });
    }

    public function testProcessEnv() {
        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $process = new Process($ssh, 'echo $FOO', null, ['FOO' => 'bar']);

            yield $process->start();

            self::assertTrue($process->isRunning());

            $exitCode = yield $process->join();
            $output = '';

            while ($read = yield $process->getStdout()->read()) {
                $output .= $read;
            }

            self::assertFalse($process->isRunning());
            self::assertEquals("bar\n", $output);
            self::assertEquals(0, $exitCode);

            $exitCode = yield $process->join();

            self::assertEquals(0, $exitCode);

            yield $ssh->close();
        });
    }

    public function testProcessBadEnv() {
        self::expectException(SessionEnvException::class);

        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $process = new Process($ssh, 'echo $FOO2', null, ['FOO2' => 'bar']);

            yield $process->start();

            self::assertTrue($process->isRunning());

            $exitCode = yield $process->join();
            $output = '';

            while ($read = yield $process->getStdout()->read()) {
                $output .= $read;
            }

            self::assertFalse($process->isRunning());
            self::assertEquals("bar\n", $output);
            self::assertEquals(0, $exitCode);

            yield $ssh->close();
        });
    }

    public function testSignal() {
        self::markTestSkipped('OpenSSH does not support receiving signal');
    }

    public function testStdin() {
        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $process = new Process($ssh, 'read foo; echo $foo');

            yield $process->start();

            self::assertTrue($process->isRunning());

            yield $process->getStdin()->write("bar\n");

            $exitCode = yield $process->join();
            $output = '';

            while ($read = yield $process->getStdout()->read()) {
                $output .= $read;
            }

            self::assertFalse($process->isRunning());
            self::assertEquals("bar\n", $output);
            self::assertEquals(0, $exitCode);

            yield $ssh->close();
        });
    }

    public function testStderr() {
        Loop::run(function () {
            $ssh = yield $this->getSsh();
            $process = new Process($ssh, '>&2 echo foo');

            yield $process->start();

            self::assertTrue($process->isRunning());

            $exitCode = yield $process->join();
            $output = '';

            while ($read = yield $process->getStderr()->read()) {
                $output .= $read;
            }

            self::assertFalse($process->isRunning());
            self::assertEquals("foo\n", $output);
            self::assertEquals(0, $exitCode);

            yield $ssh->close();
        });
    }

    /**
     * If connection closed by server and process started then fail with channel error.
     */
    public function testProcessFailOnDisconnect() {
        $this->expectException(ChannelException::class);
        Loop::run(function () {
            /** @var SshResource $ssh */
            $ssh = yield $this->getSsh();

            $process = new Process($ssh, 'sleep 10; echo test;');

            yield $process->start();
            self::assertTrue($process->isRunning());
            Loop::defer(function () use ($ssh) {
                NetworkHelper::disconnect($ssh);
            });
            yield $process->join();
        });
    }

    /**
     * If channel closed then join must resolve with false exitCode
     * Some implementations doesn't send exit code.
     * In that cases false must be used.
     */
    public function testProcessFinishWithFalseOnChannelClose() {
        Loop::run(function () {
            /** @var SshResource $ssh */
            $ssh = yield $this->getSsh();

            $process = new Process($ssh, 'sleep 10; echo test;');

            yield $process->start();
            self::assertTrue($process->isRunning());
            Loop::defer(function () use ($ssh) {
                $ssh->close();
            });
            $exitCode = yield $process->join();
            self::assertFalse($exitCode, false);
        });
    }
}
