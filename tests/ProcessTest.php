<?php

namespace Amp\Ssh\Tests;

use Amp\Loop;
use Amp\Ssh\Authentication\UsernamePassword;
use Amp\Ssh\Process;
use PHPUnit\Framework\TestCase;
use function Amp\Ssh\connect;

class ProcessTest extends TestCase {
    private $logger;

    public function setUp() {
        $this->logger = new \Monolog\Logger('ampssh', [
            new \Monolog\Handler\StreamHandler(\fopen('debug.log', 'w+'))
        ]);
    }

    public function testProcess() {
        Loop::run(function () {
            $authentication = new UsernamePassword('root', 'root');
            $sshResource = yield connect('127.0.0.1:2222', $authentication, $this->logger);

            $process = new Process($sshResource, 'echo foo');

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

            yield $sshResource->close();
        });
    }
}
