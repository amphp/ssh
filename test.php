<?php

require_once __DIR__ . '/vendor/autoload.php';

Amp\Loop::run(function () {
    $logger = new \Monolog\Logger('ampssh', [
        new \Monolog\Handler\StreamHandler(STDOUT)
    ]);

    $username = get_current_user();
    $home = getenv('HOME');

    $authentication = new \Amp\SSH\Authentication\PublicKey($username, $home . '/.ssh/id_rsa');
    $sshResource = yield \Amp\SSH\connect('127.0.0.1:22', $authentication, null);

    $process = new \Amp\SSH\Shell($sshResource);
    yield $process->start();

    \Amp\ByteStream\pipe($process->getStderr(), new \Amp\ByteStream\ResourceOutputStream(STDERR));
    \Amp\ByteStream\pipe($process->getStdout(), new \Amp\ByteStream\ResourceOutputStream(STDOUT));

    $stdin = new \Amp\ByteStream\ResourceInputStream(STDIN);

    \Amp\asyncCall(function () use($process, $stdin) {
        yield $process->join();
        $stdin->close();
    });

    Amp\Loop::delay(10000, function () use($process) {
        yield $process->signal(SIGKILL);
    });

    while ($process->isRunning()) {
        $read = yield $stdin->read();

        if ($read !== null) {
            yield $process->getStdin()->write($read);
        }
    }

    $sshResource->close();

    Amp\Loop::stop();
});
