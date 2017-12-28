<?php

declare(strict_types=1);

namespace Amp\SSH;

use function Amp\asyncCall;
use Amp\Promise;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;
use Amp\SSH\Message\Message;
use Amp\SSH\Transport\BinaryPacketHandler;
use Amp\SSH\Transport\BinaryPacketWriter;

class Loop implements BinaryPacketWriter
{
    private $handler;

    private $ons = [];

    public function __construct(BinaryPacketHandler $handler)
    {
        $this->handler = $handler;
    }

    public function on(int $type, \Closure $closure): void
    {
        if (!array_key_exists($type, $this->ons)) {
            $this->ons[$type] = [];
        }

        $this->ons[$type][] = $closure;
    }

    public function run()
    {
        asyncCall(function () {
            while (true) {
                /** @var Message $message */
                $message = yield $this->handler->read();
                $type = $message::getNumber();

                if (array_key_exists($type, $this->ons)) {
                    foreach ($this->ons[$type] as $closure) {
                        asyncCall($closure($message));
                    }
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
