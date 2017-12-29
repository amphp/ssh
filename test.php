<?php

require_once __DIR__ . '/vendor/autoload.php';

Amp\Loop::run(function () {
    $logger = new \Monolog\Logger('ampssh', [
        new \Monolog\Handler\StreamHandler(STDOUT)
    ]);
    $sshResource = yield \Amp\SSH\connect('127.0.0.1:22', 'foo', 'bar', $logger);

    $process = new \Amp\SSH\Process($sshResource, 'printenv', null, ['PATH' => '']);
    yield $process->start();

    var_dump(yield $process->getStdout()->read());
    var_dump(yield $process->getStderr()->read());
    var_dump(yield $process->join());
});
