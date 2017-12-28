<?php

declare(strict_types=1);

namespace Amp\SSH;

use function Amp\asyncCall;
use Amp\Promise;
use Amp\SSH\Message;

class Loop
{
    private $handler;

    private $ons = [];

    public function __construct(BinaryPacketHandler $handler)
    {
        $this->handler = $handler;
    }

    public function on(int $type, \Closure $closure)
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
                $packet = yield $this->handler->read();
                $type = unpack('C', $packet)[1];

                if (array_key_exists($type, $this->registry)) {
                    $class = $this->registry[$type];
                    $packet = $class::decode($packet);
                }

                if (array_key_exists($type, $this->ons)) {
                    /** @var \Closure $closure */
                    foreach ($this->ons[$type] as $closure) {
                        asyncCall($closure($packet));
                    }
                }
            }
        });
    }
}
