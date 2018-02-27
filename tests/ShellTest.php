<?php

namespace Amp\Ssh\Tests;

use Amp\Loop;
use Amp\Ssh\Authentication\UsernamePassword;
use Amp\Ssh\Shell;
use PHPUnit\Framework\TestCase;
use function Amp\call;
use function Amp\Ssh\connect;

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
}
