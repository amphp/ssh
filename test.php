<?php

require_once __DIR__ . '/vendor/autoload.php';

Amp\Loop::run(function () {
    $logger = new \Monolog\Logger('ampssh', [
        new \Monolog\Handler\StreamHandler(STDOUT)
    ]);
    $sshResource = yield \Amp\SSH\connect('127.0.0.1:22', 'foo', 'bar', null);

    $process = new \Amp\SSH\Shell($sshResource);
    yield $process->start();

    \Amp\Loop::defer(function () use($process) {
        while (true) {
            $stderr = yield $process->getStderr()->read();
            fwrite(STDERR, $stderr);
        }
    });

    \Amp\Loop::defer(function () use($process) {
        while (true) {
            $stdout = yield $process->getStdout()->read();
            fwrite(STDOUT, $stdout);
        }
    });

    \Amp\ByteStream\pipe(new \Amp\ByteStream\ResourceInputStream(STDIN), $process->getStdin());

    $stdin = new \Amp\ByteStream\ResourceInputStream(STDIN);

    while ($process->isRunning()) {
        $read = yield $stdin->read();
        yield $process->getStdin()->write($read);
    }

    Amp\Loop::stop();
});
