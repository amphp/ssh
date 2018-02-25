<?php

namespace Amp\Ssh\Tests;

use PhpCsFixer\Tests\TestCase;

class ProcessTest extends TestCase
{
    public function testProcess()
    {
        \Amp\Loop::run(function () {
            $authentication = new \Amp\Ssh\Authentication\UsernamePassword('root', 'root');
            $sshResource = yield \Amp\Ssh\connect('127.0.0.1:2222', $authentication);

            $process = new \Amp\Ssh\Process($sshResource, 'echo foo');

            yield $process->start();
            $output = yield $process->getStdout()->read();
            yield $process->join();

            self::assertEquals("foo\n", $output);

            yield $sshResource->close();
        });
    }
}