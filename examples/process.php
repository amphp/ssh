<?php

require_once __DIR__ . '/../vendor/autoload.php';

Amp\Loop::run(function () {
    $logger = new \Monolog\Logger('ampssh', [
        new \Monolog\Handler\StreamHandler(STDOUT)
    ]);

    $username = \get_current_user();
    $home = \getenv('HOME');

    $authentication = new \Amp\SSH\Authentication\PublicKey($username, $home . '/.ssh/id_rsa');
    $sshResource = yield \Amp\SSH\connect('127.0.0.1:22', $authentication, $logger);

    $process = new \Amp\SSH\Process($sshResource, 'ls -la');

    \Amp\ByteStream\pipe($process->getStderr(), new \Amp\ByteStream\ResourceOutputStream(STDERR));
    \Amp\ByteStream\pipe($process->getStdout(), new \Amp\ByteStream\ResourceOutputStream(STDOUT));

    yield $process->start();
    yield $process->join();

    yield $sshResource->close();
});
