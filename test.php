<?php

require_once __DIR__ . '/vendor/autoload.php';

Amp\Loop::run(function () {
    $logger = new \Monolog\Logger('ampssh', [
        new \Monolog\Handler\StreamHandler(STDOUT)
    ]);
    $loop = yield \Amp\SSH\connect('127.0.0.1:22', 'foo', 'bar', $logger);

    $dispatcher = new \Amp\SSH\Channel\Dispatcher($loop);
    /** @var \Amp\SSH\Channel\Session $session */
    $session = yield $dispatcher->createSession();
    $session->exec('ls -la');
});
