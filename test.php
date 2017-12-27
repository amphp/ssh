<?php

require_once __DIR__ . '/vendor/autoload.php';

Amp\Loop::run(function () {
    $socket = yield \Amp\Socket\connect('tcp://127.0.0.1:22');
    $transport = new \Amp\SSH\Transport($socket);

    /** @var \Amp\SSH\BinaryPacketHandler $binaryPacketHandler */
    $binaryPacketHandler = yield $transport->initialize();

    $auth = new \Amp\SSH\Authentication\UsernamePassword('foo', 'bar');
    $isAuth = yield $auth->authenticate($binaryPacketHandler);

    var_dump($isAuth);
});
