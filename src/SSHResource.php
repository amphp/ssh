<?php

declare(strict_types=1);

namespace Amp\SSH;

use function Amp\asyncCall;
use Amp\Promise;
use Amp\SSH\Channel\Dispatcher;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;
use Amp\SSH\Message\Message;
use Amp\SSH\Transport\BinaryPacketHandler;
use Amp\SSH\Transport\BinaryPacketWriter;

class SSHResource extends EventEmitter implements BinaryPacketWriter
{
    private $handler;

    private $dispatcher;

    public function __construct(BinaryPacketHandler $handler)
    {
        $this->handler = $handler;
        $this->dispatcher = new Dispatcher($this);
    }

    public function createSession()
    {
        return $this->dispatcher->createSession();
    }

    public function loop()
    {
        asyncCall(function () {
            while (true) {
                /** @var Message $message */
                $message = yield $this->handler->read();

                if ($message instanceof Message) {
                    $type = $message::getNumber();

                    $this->emit($type, $message);
                }
            }
        });
    }

    final public function updateEncryption(Encryption $encryption, Mac $encryptMac): void
    {
        throw new \RuntimeException('Not allowed');
    }

    public function write($message): Promise
    {
        return $this->handler->write($message);
    }
}
