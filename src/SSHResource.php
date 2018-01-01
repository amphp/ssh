<?php

declare(strict_types=1);

namespace Amp\SSH;

use function Amp\asyncCall;
use function Amp\call;
use Amp\Promise;
use Amp\SSH\Channel\Dispatcher;
use Amp\SSH\Channel\Session;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;
use Amp\SSH\Message\Disconnect;
use Amp\SSH\Message\Message;
use Amp\SSH\Transport\BinaryPacketHandler;
use Amp\SSH\Transport\BinaryPacketWriter;

/**
 * @internal
 */
class SSHResource
{
    private $handler;

    private $dispatcher;

    private $running = true;

    public function __construct(BinaryPacketHandler $handler, Dispatcher $dispatcher)
    {
        $this->handler = $handler;
        $this->dispatcher = $dispatcher;
    }

    public function createSession(): Session
    {
        return $this->dispatcher->createSession();
    }

    public function close(): Promise
    {
        return call(function () {
            $this->running = false;
            $this->dispatcher->close();

            yield $this->handler->write(new Disconnect);

            $this->handler->close();
        });
    }
}
