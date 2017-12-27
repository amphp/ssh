<?php

require_once __DIR__ . '/vendor/autoload.php';

Amp\Loop::run(function () {
    $socket = yield \Amp\Socket\connect('tcp://127.0.0.1:22');
    $transport = new \Amp\SSH\Transport($socket);

    /** @var \Amp\SSH\BinaryPacketHandler $binaryPacketHandler */
    $binaryPacketHandler = yield $transport->initialize();

    // Not working
    $payload = pack(
        'CNa*',
        Amp\SSH\Message\Message::SSH_MSG_SERVICE_REQUEST,
        strlen('ssh-userauth'),
        'ssh-userauth'
    );

    $binaryPacketHandler->write($payload);
    $packet = yield $binaryPacketHandler->read();

    var_dump($packet);
});
