<?php

use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use Amp\Ssh\Tunnel;
use Monolog\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

Amp\Loop::run(function () {
    $logger = new Logger('ampssh', [
        new \Monolog\Handler\StreamHandler(STDOUT, Logger::INFO),
    ]);

    $authentication = new \Amp\Ssh\Authentication\UsernamePassword('root', 'root');
    $sshResource = yield \Amp\Ssh\connect('127.0.0.1:22', $authentication, $logger);

    /** @var ClientSocket $tunnel */
    $tunnel = yield Tunnel::connect($sshResource, 'httpbin.org', '443', '127.0.0.1', '443');
    yield $tunnel->enableCrypto((new ClientTlsContext)->withPeerName('httpbin.org'));
    $httpMessage = "GET / HTTP/1.0\r\nHost: httpbin.org\r\nUser-Agent: Yolo\r\nConnection: close\r\nContent-Length: 0\r\n\r\n";

    yield $tunnel->write($httpMessage);

    while (null !== $read = yield $tunnel->read()) {
        echo $read;
    }

    exit;
});
