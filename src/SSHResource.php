<?php

namespace Amp\SSH;

use Amp\Promise;
use Amp\SSH\Channel\Dispatcher;
use Amp\SSH\Channel\Session;
use Amp\SSH\Message\Disconnect;
use Amp\SSH\Transport\BinaryPacketHandler;
use function Amp\call;

/**
 * @internal
 */
class SSHResource {
    private $handler;

    private $dispatcher;

    private $running = true;

    public function __construct(BinaryPacketHandler $handler, Dispatcher $dispatcher) {
        $this->handler = $handler;
        $this->dispatcher = $dispatcher;
    }

    public function createSession(): Session {
        return $this->dispatcher->createSession();
    }

    public function close(): Promise {
        return call(function () {
            $this->running = false;
            $this->dispatcher->close();

            yield $this->handler->write(new Disconnect);

            $this->handler->close();
        });
    }
}
