<?php

require_once __DIR__ . '/../vendor/autoload.php';

Amp\Loop::run(function () {
    $username = \get_current_user();
    $home = \getenv('HOME');

    $authentication = new \Amp\Ssh\Authentication\PublicKey($username, $home . '/.ssh/id_rsa');
    $sshResource = yield \Amp\Ssh\connect('127.0.0.1:22', $authentication);

    $shell = new \Amp\Ssh\Shell($sshResource);
    yield $shell->start();

    \Amp\ByteStream\pipe($shell->getStderr(), new \Amp\ByteStream\ResourceOutputStream(STDERR));
    \Amp\ByteStream\pipe($shell->getStdout(), new \Amp\ByteStream\ResourceOutputStream(STDOUT));

    $stdin = new \Amp\ByteStream\ResourceInputStream(STDIN);

    \Amp\asyncCall(function () use ($shell, $stdin) {
        yield $shell->join();
        $stdin->close();
    });

    while ($shell->isRunning()) {
        $read = yield $stdin->read();

        if ($read !== null) {
            yield $shell->getStdin()->write($read);
        }
    }

    yield $sshResource->close();
});
