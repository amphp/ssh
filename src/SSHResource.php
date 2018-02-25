<?php

namespace Amp\Ssh;

use Amp\Promise;
use Amp\Ssh\Channel\Dispatcher;
use Amp\Ssh\Channel\Session;
use Amp\Ssh\Message\Disconnect;
use Amp\Ssh\Transport\BinaryPacketHandler;
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

    public function __destruct()
    {
        if ($this->running) {
            $this->close();
        }
    }
}
