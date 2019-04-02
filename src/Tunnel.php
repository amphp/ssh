<?php

namespace Amp\Ssh;

use Amp\Promise;
use Amp\Socket;
use Amp\Ssh\Channel\ChannelInputStream;
use Amp\Ssh\Channel\ChannelOutputStream;
use function Amp\asyncCall;
use function Amp\ByteStream\pipe;
use function Amp\call;
use function Amp\Socket\listen;

class Tunnel
{
    /**
     * @param SshResource $sshResource
     * @param string      $host
     * @param int         $port
     * @param string      $originHost
     * @param string      $originPort
     *
     * @return Promise
     * @throws \Amp\Socket\SocketException
     */
    public static function connect(
        SshResource $sshResource,
        string $host,
        int $port,
        string $originHost,
        string $originPort
    ): Promise {
        $directTcpIp = $sshResource->createDirectTcpIp($host, $port, $originHost, $originPort);
        $input = new ChannelInputStream($directTcpIp->getDataEmitter()->iterate());
        $output = new ChannelOutputStream($directTcpIp);

        return call(static function () use ($directTcpIp, $input, $output) {
            yield $directTcpIp->open();

            $server = listen('127.0.0.1:0');

            // FIXME: This is just a PoC, socket connects need to be authenticated.

            /** @var Socket\ClientSocket $socket */
            /** @var Socket\ServerSocket $intermediate */
            list($socket, $intermediate) = yield [Socket\connect($server->getAddress()), $server->accept()];

            asyncCall(static function () use ($intermediate, $output) {
                try {
                    yield pipe($intermediate, $output);
                    yield $output->end();
                } catch (\Throwable $e) {
                    $intermediate->close();
                }
            });

            asyncCall(static function () use ($intermediate, $input) {
                try {
                    yield pipe($input, $intermediate);
                    yield $intermediate->end();
                } catch (\Throwable $e) {
                    $intermediate->close();
                }
            });

            return $socket;
        });
    }
}
