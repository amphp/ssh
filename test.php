<?php

require_once __DIR__ . '/vendor/autoload.php';

Amp\Loop::run(function () {
    $logger = new \Monolog\Logger('ampssh', [
        new \Monolog\Handler\StreamHandler(STDOUT)
    ]);
    $loop = yield \Amp\SSH\connect('127.0.0.1:22', 'foo', 'bar', $logger);

    $channel = new \Amp\SSH\Channel\Channel($loop, 0);
    yield $channel->initialize();
});
