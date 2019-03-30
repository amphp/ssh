<?php

require_once __DIR__ . '/../vendor/autoload.php';

Amp\Loop::run(function () {
    $logger = new \Monolog\Logger('ampssh', [
        new \Monolog\Handler\StreamHandler(STDOUT)
    ]);

    $authentication = new \Amp\Ssh\Authentication\UsernamePassword('root', 'root');
    $sshResource = yield \Amp\Ssh\connect('127.0.0.1:2222', $authentication, $logger);

    $tunnel = new \Amp\Ssh\Tunnel($sshResource, 'httpbin.org', '80', '127.0.0.1', '80');
    $httpMessage = "GET / HTTP/1.1\r\nHost: httpbin.org\r\nUser-Agent: Yolo\r\nContent-Length: 0\r\n\r\n";

    yield $tunnel->write($httpMessage);

    while (null !== $read = yield $tunnel->read()) {
        echo $read;
    }
});
